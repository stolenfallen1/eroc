<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Itemmasters;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\HIS\MedsysCashAssessment;
use App\Models\HIS\his_functions\CashAssessment;
use DB;
use \Carbon\Carbon;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\tbInvStockCard;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\User;
use App\Helpers\GetIP;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\medsys\MedSysDailyOut;
use App\Helpers\HIS\MedicineSuppliesCharges\MedicineSuppliesUseSequences;
use App\Helpers\HIS\MedicineSuppliesCharges\MedicineSuppliesPrepareData;

class EmergencyRoomMedicine extends Controller
{
    //
    protected $check_is_allow_medsys;
    protected $medicineSuppliesSequence;
    protected $medicineSuppliesData;
    public function __construct() {
        $this->isproduction = true;
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->medicineSuppliesSequence = new MedicineSuppliesUseSequences();
        $this->medicineSuppliesData = new MedicineSuppliesPrepareData();
    }
    
    public function erRoomMedicine(Request $request) {
        try {
            $revenueCode = TransactionCodes::where("code",$request->revenuecode)->first();
            if (!$revenueCode) {
                return response()->json(["msg" => "Revenue code not found"], 404);
            }
            $warehouseItems = Warehouseitems::where('warehouse_Id', $request->warehouseID)->pluck('item_Id');
            $warehouseItemsArray = $warehouseItems->toArray();
            if (empty($warehouseItemsArray)) {
                return response()->json(["msg" => "Warehouse items not found"], 404);
            }
            $priceColumn = $request->patienttype == 1 ? 'item_Selling_Price_Out' : 'item_Selling_Price_In';
            $items = Itemmasters::with(['wareHouseItems' => function ($query) use ($request, $priceColumn) {
                $query->where('warehouse_Id', $request->warehouseID)
                    ->select('id', 'item_Id', 'item_OnHand', 'item_ListCost', DB::raw("$priceColumn as price"));
            }])
            ->whereIn('id', $warehouseItemsArray) 
            ->orderBy('item_name', 'asc');
            if($request->keyword) {
                $items->where('item_name','LIKE','%'.$request->keyword.'%');
            }
            $page  = $request->per_page ?? '15';
            return response()->json($items->paginate($page), 200);
        } catch(Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function chargePatientMedicineSupply(Request $request) {
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        try {
            $checkUser = User::where([
                ['idnumber', '=', $request->payload['user_userid']],
                ['passcode', '=', $request->payload['user_passcode']]
            ])->first();
    
            if (!$checkUser) {
                return response()->json(['message' => 'Incorrect Username or Password'], 404);
            }
            if (isset($request->payload['Medicines']) && 
                count(array_filter($request->payload['Medicines'], function($item) {
                    return !empty($item['code']);
                })) > 0) {
                $this->processItems($request, $checkUser, 'Medicines');
            }
            if(isset($request->payload['itemListCost'])) {
                $this->processItems($request, $checkUser, 'itemListCost');
            }
            if (isset($request->payload['Supplies']) && 
                count(array_filter($request->payload['Supplies'], function($item) {
                    return !empty($item['code']);
                })) > 0) {
                $this->processItems($request, $checkUser, 'Supplies');
            }
    
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_inventory')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_billingOut')->commit();

            return response()->json(['message' => 'Charge processing successful'], 200);

        } catch (Exception $e) {

            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();

            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }
    
    private function processItems($request, $checkUser, $itemType) {
        $tbNursePHSlipSequence = $this->medicineSuppliesSequence->handleTbNursePHSlipSequence();
        $tbInvChargeSlipSequence = $this->medicineSuppliesSequence->handleTbInvChargeSlipSequence();
        $medsysCashAssessmentSequence = $this->medicineSuppliesSequence->handleMedsysCashAssessmentSequence();
        
        foreach ($request->payload[$itemType] as $index => $item) {
            if (!isset($item['code'], $item['item_name'], $item['quantity'], $item['amount'])) {
                return response()->json(['message' => "Missing required $itemType information"], 400);
            }
            if($itemType === 'Medicines') {
                $itemID   = $request->payload['medicine_stocks_OnHand'][$index]['medicine_id'] ?? null;
                $listCost = $request->payload['medicine_stocks_OnHand'][$index]['item_List_Cost'] ?? null;
                $stock   = intval($request->payload['medicine_stocks_OnHand'][$index]['medicine_stock']) ?? null;
            } else if('Supplies') {
                $itemID     = $request->payload['supply_stocks_OnHand'][$index]['supply_id'] ?? null;
                $listCost   = $request->payload['supply_stocks_OnHand'][$index]['item_List_Cost'] ?? null;
                $stock      = intval($request->payload['supply_stocks_OnHand'][$index]['supply_stock']) ?? null;
            }
            if($request->payload['charge_to'] === 'Company / Insurance') {
                $tb_medsys_nurse_logbook = tbNurseLogBook::create($this->medicineSuppliesData->prepareMedsysLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID));
                $cdg_nurse_logbook = NurseLogBook::create($this->medicineSuppliesData->prepareNurseLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID));
                $CDG_MMIS_inventory_trasaction = InventoryTransaction::create($this->medicineSuppliesData->prepareInventoryTransactionData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $stock));
                $inventory_tb_stock_card = tbInvStockCard::create($this->medicineSuppliesData->prepareStockCardData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $listCost));
                if(!$tb_medsys_nurse_logbook || !$cdg_nurse_logbook || !$CDG_MMIS_inventory_trasaction || !$inventory_tb_stock_card) {
                    throw new Exception('Failed to charge item');
                }
            } else {
                $cdg_cash_assessment = CashAssessment::create($this->medicineSuppliesData->prepareCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence));
                $medsys_cash_assessment = MedsysCashAssessment::create($this->medicineSuppliesData->prepareMedsysCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence));
                if(!$cdg_cash_assessment || !$medsys_cash_assessment) {
                    throw new Exception('Failed to charge item');
                }
            }
        }
    }
    
    public function getMedicineSupplyCharges($id) {
        try {
            $accountType = DB::connection('sqlsrv_patient_data')->table('CDG_PATIENT_DATA.dbo.PatientRegistry')->select('guarantor_Name')->where('case_No', $id)->first();

            if($accountType->guarantor_Name === 'Self Pay') {
                $dataCharges = DB::table('CDG_BILLING.dbo.CashAssessment as ca')
                ->select(
                    'ca.patient_Id',
                    'ca.case_No',
                    'ca.assessnum',
                    'ca.revenueID',
                    'ca.refNum',
                    'ca.itemID',
                    'ca.quantity',
                    'ca.amount',
                    'ca.dosage',
                    'ca.recordStatus',
                    'ca.requestDescription',
                    'ca.ORNumber',
                    'mscD.description as frequency'
                )
                ->leftJoin('CDG_CORE.dbo.mscDosages as mscD', 'ca.dosage', '=', 'mscD.dosage_id')
                ->where('ca.case_No', '=', $id)
                ->where('ca.recordStatus', '!=', 'R')
                ->get();

            } else {
                $dataCharges = DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
                    ->select(
                        'cdgLB.patient_Id',
                        'cdgLB.case_No',
                        'cdgLB.revenue_Id',
                        'cdgLB.requestNum',
                        'cdgLB.referenceNum',
                        'cdgLB.item_Id',
                        'cdgLB.description',
                        'cdgLB.Quantity',
                        'cdgLB.item_OnHand',
                        'cdgLB.price',
                        'cdgLB.dosage',
                        'cdgLB.amount',
                        'cdgLB.record_Status',
                        'mscD.description as frequency'
                    )
                    ->leftjoin('CDG_CORE.dbo.mscDosages as mscD', 'cdgLB.dosage', '=', 'mscD.dosage_id')
                    ->where('cdgLB.case_No', '=', $id)
                    ->get();
            }

            $charges = $dataCharges->map(function($item) {
                return [
                    'revenue_Id'    => $item->revenue_Id ?? $item->revenueID,
                    'record_Status' => $item->record_Status ?? $item->recordStatus,
                    'item_Id'       => $item->item_Id ?? $item->itemID,
                    'description'   => (isset($item->requestDescription) 
                                    ?  $item->requestDescription 
                                    :  $item->description ?? null),
                    'price'         => $item->price ?? null,
                    'Quantity'      => $item->Quantity ?? $item->quantity,
                    'dosage'        => $item->dosage ?? $item->dosage,
                    'amount'        => $item->amount ?? $item->amount,
                    'referenceNum'  => $item->referenceNum ?? $item->refNum,
                    'assessnum'     => $item->assessnum ?? null,
                    'frequency'     => $item->frequency ?? null,
                    'ORN'           => $item->ORNumber  ?? null,
                ];
                
            });

            if ($dataCharges->isEmpty()) {
                return response()->json([
                    'message' => 'No Charges'
                ], 404);
            }

            return response()->json($charges, 200);

        } catch (Exception $e) {

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function revokedCharges(Request $request) {
        $this->handleDatabaseTransactionProcess('start');
        try {
            $checkUser = User::where([
                ['idnumber', '=', $request->payload['user_userid']],
                ['passcode', '=', $request->payload['user_passcode']]
            ])->first();

            if (!$checkUser) {
                return response()->json(['message' => 'Incorrect Username or Password'], 404);
            }

            $hmoCount = NurseLogBook::where('referenceNum', $request->payload['reference_id'])->count();
            $selfPayCount = CashAssessment::where('refNum', $request->payload['reference_id'])->count();

            if($request->payload['account'] !=='Self-Pay') {
                if($hmoCount === 0) {
                    return response()->json(['message' => 'No charges found'], 404);
                }
                while($hmoCount > 0 ) {
                    //to follow
                }
            } else {
            }
            $this->handleDatabaseTransactionProcess('commit');
            return response()->json(['message' => 'Charges successfully revoked'], 200);

        } catch (Exception $e) {
            $this->handleDatabaseTransactionProcess('rollback');
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function handleDatabaseTransactionProcess($progressStatus) {
        switch ($progressStatus) {
            case 'start':
                $this->handleStartTransaction();
                break;
            case 'commit':
                $this->handleCommitTransaction();
                break;
            case 'rollback':
                $this->handleRollbackTransaction();
                break;
        }
    }

    private function handleStartTransaction() {
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
    }

    private function handleCommitTransaction() {
        DB::connection('sqlsrv_medsys_nurse_station')->commit();
        DB::connection('sqlsrv_patient_data')->commit();
        DB::connection('sqlsrv_mmis')->commit();
        DB::connection('sqlsrv_medsys_billing')->commit();
        DB::connection('sqlsrv_billingOut')->commit();
        DB::connection('sqlsrv_medsys_billing')->commit();
    }

    private function handleRollbackTransaction() {
        DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
        DB::connection('sqlsrv_patient_data')->rollBack();
        DB::connection('sqlsrv_mmis')->rollBack();
        DB::connection('sqlsrv_medsys_billing')->rollBack();
        DB::connection('sqlsrv_billingOut')->rollBack();
        DB::connection('sqlsrv_medsys_billing')->rollBack();
    }
    
    
    public function cancelCharges(Request $request) {

        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();

        try{
            $checkUser = User::where([
                ['idnumber', '=', $request->payload['user_userid']],
                ['passcode', '=', $request->payload['user_passcode']]
            ])->first();

            if (!$checkUser) {
                return response()->json(['message' => 'Incorrect Username or Password'], 404);
            }

            $cdg_mmis_inventory         = InventoryTransaction::where('trasanction_Reference_Number', $request->payload['reference_id'])->first();
            $medsys_inventory           = tbInvStockCard::where('RefNum', $request->payload['reference_id'])->first();
            $getCashAssessment          = CashAssessment::where('RefNum', $request->payload['reference_id'])->first();
            $getMedsysCashAssessment    = MedsysCashAssessment::where('RefNum', $request->payload['reference_id'])->first();
            $isUseReferenceNumber       = NurseLogBook::where('referenceNum', $request->payload['reference_id'])->first();
            $isUseRequestNumber         = NurseLogBook::where('requestNum', $request->payload['reference_id'])->first();
            $billingOut                 = HISBillingOut::where('refNum', $request->payload['reference_id'])->first();
            $medsys_billingOut          = MedSysDailyOut::where('RefNum', $request->payload['reference_id'])->first();

            $count = NurseLogBook::where('referenceNum', $request->payload['reference_id'])->count();

            $is_MMIS_Created_Successfuly = true; 
            $isCreated_medsys_Inventory_successfuly = true; 
            $is_update_successful = true;
            $is_revoked_billing = true;

            $isHMO =  ($cdg_mmis_inventory && $medsys_inventory) || $request->payload['account'] !== 'Self-Pay';

            while($count > 0) {
                if($isHMO) {
                    if($billingOut) {
                        $is_updated_billingOut = HISBillingOut::where('refNum',  $request->payload['reference_id'])
                            ->update([
                                'refNum' => $request->payload['reference_id'] . '[REVOKED]',
                                'ChargeSlip' => $request->payload['reference_id'] . '[REVOKED]',
                            ]);

                        $is_updated_medsys_billingOut = MedSysDailyOut::where('RefNum', $request->payload['reference_id'])
                            ->update([
                                'RefNum' => $request->payload['reference_id'] . '[REVOKED]',
                                'ChargeSlip' => $request->payload['reference_id'] . '[REVOKED]',
                            ]);
                        
                        $is_created_billing = HISBillingOut::create($this->BillingOutData($request, $billingOut, $checkUser));
                        $is_created_medsys_billing = MedSysDailyOut::create($this->MedsysBillingOutData($request,  $medsys_billingOut, $checkUser));
                        $is_revoked_billing = $is_updated_billingOut && $is_created_billing  && $is_updated_medsys_billingOut && $is_created_medsys_billing ?  true : false;
                    
                    }
                    if($cdg_mmis_inventory) {
                        $isCreated_cdg_MMIS = InventoryTransaction::create($this->CDGMMISInventoryData($request, $cdg_mmis_inventory, $checkUser));
                        $is_MMIS_Created_Successfuly = $isCreated_cdg_MMIS ? true : false;
                    }
                    
                    if($medsys_inventory) {
                        $isCreated_medsys_Inventory  = tbInvStockCard::create($this->MedsysStockCardData($request,  $medsys_inventory, $checkUser));
                        $isCreated_medsys_Inventory_successfuly = $isCreated_medsys_Inventory ? true : false;
                    }
                    if($isUseReferenceNumber) {
                        $isUpdated_cdg_lb  = NurseLogBook::where('referenceNum', $request->payload['reference_id'])->update(['record_Status' => 'R']);
                        $isUpdated_medsys_lb  = tbNurseLogBook::where('ReferenceNum', $request->payload['reference_id'])->update(['RecordStatus' => 'R']);
                        $is_update_successful = $isUpdated_cdg_lb && $isUpdated_medsys_lb ? true : false;
                    } 
                    if($isUseRequestNumber) {
                        $isUpdated_cdg_lb  = NurseLogBook::where('requestNum', $request->payload['reference_id'])->update(['record_Status' => 'R']);
                        $isUpdated_medsys_lb  = tbNurseLogBook::where('RequestNum', $request->payload['reference_id'])->update(['RecordStatus' => 'R']);
                        $is_update_successful = $isUpdated_cdg_lb && $isUpdated_medsys_lb ? true : false;
                    }

                    if(!$is_MMIS_Created_Successfuly || !$isCreated_medsys_Inventory_successfuly || !$is_update_successful || !$is_revoked_billing) {
                        throw new Exception('Failed to cancel charges');
                    }
        

                } else {
                    $is_Row_Created_Successfuly = true;
                    $is_Medsys_Row_Created_Successfuly = true;

                    $isUpdatedRow = CashAssessment::where('refNum', $request->payload['reference_id'])->update([
                        'recordStatus'  => 'R',
                        'dateRevoked'   => Carbon::now(),
                        'revokedBy'     => $checkUser->idnumber,
                    ]);

                    if($getCashAssessment) {
                        $isRowCreated = CashAssessment::create($this->CDGCashAssessmentData($request, $getCashAssessment, $checkUser));
                        $is_Row_Created_Successfuly = $isRowCreated ? true : false;
                    }

                    $isMedysUpdatedRow = MedsysCashAssessment::where('RefNum', $request->payload['reference_id'])->update([
                        'RecordStatus'  => 'R',
                        'DateRevoked'   => Carbon::now(),
                        'RevokedBy'     => $checkUser->idnumber
                    ]);

                    if($getMedsysCashAssessment) {
                        $isMedsysRowCreated = MedsysCashAssessment::create($this->MedsysCashAssessmentData($request, $getMedsysCashAssessment, $checkUser));
                        $is_Medsys_Row_Created_Successfuly = $isMedsysRowCreated ? true : false;
                    }

                    if(!$isUpdatedRow || !$is_Row_Created_Successfuly || !$isMedysUpdatedRow || !$is_Medsys_Row_Created_Successfuly) {
                        throw new Exception('Failed to cancel charges');
                    }
                }
                $count--;
            }
        
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();

            return response()->json([
                'message'   => 'Item Charged has been successfully Canceleled'
            ], 200);

        } catch(Exception $e) {

            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();

            return response()->json([
                'message' => 'Error!' . $e->getMessage()
            ], 500);
        }
    }

    private function CDGMMISInventoryData($request,  $cdg_mmis_inventory, $checkUser) {
        return [
            'branch_Id'                         => 1,
            'warehouse_Group_Id'                => $cdg_mmis_inventory->warehouse_Group_Id,
            'warehouse_Id'                      => $cdg_mmis_inventory->warehouse_Id,
            'patient_Id'                        => $cdg_mmis_inventory->patient_Id,
            'patient_Registry_Id'               => $cdg_mmis_inventory->patient_Registry_Id,    
            'transaction_Item_Id'               => $cdg_mmis_inventory->transaction_Item_Id,
            'transaction_Date'                  => Carbon::now(),
            'trasanction_Reference_Number'      => $cdg_mmis_inventory->trasanction_Reference_Number,
            'transaction_Acctg_TransType'       => $cdg_mmis_inventory->transaction_Acctg_TransType,
            'transaction_Qty'                   => (intval($cdg_mmis_inventory->transaction_Qty) * -1),
            'transaction_Item_OnHand'           => $cdg_mmis_inventory->transaction_Item_OnHand,
            'transaction_Item_ListCost'         => $cdg_mmis_inventory->transaction_Item_ListCost,
            'transaction_Requesting_Number'     => $cdg_mmis_inventory->transaction_Requesting_Number,
            'transaction_UserId'                => $checkUser->idnumber,
            'created_at'                        => Carbon::now(),
            'createdBy'                         => $checkUser->idnumber,
            'updated_at'                        => Carbon::now(),
            'updatedby'                         => $checkUser->idnumber,
        ];
    }

    private function MedsysStockCardData($request,  $medsys_inventory, $checkUser) {
        return [
            'SummaryCode'   => $medsys_inventory->SummaryCode,
            'HospNum'       => $request->payload['patient_Id'] ?? null,
            'IdNum'         => $request->payload['case_No'] . 'B' ?? null,
            'ItemID'        => $medsys_inventory->ItemID,
            'TransDate'     => Carbon::now(),
            'RevenueID'     => $medsys_inventory->RevenueID,
            'RefNum'        => $medsys_inventory->RefNum,
            'Status'        => $medsys_inventory->Status,
            'Quantity'      => intval($medsys_inventory->Quantity) * -1,
            'Balance'       => isset($medsys_inventory->Balance) 
                            ? $medsys_inventory->Balance 
                            : null,
            'NetCost'       => isset($medsys_inventory->NetCost) 
                            ? floatval($medsys_inventory->NetCost) 
                            : null,
            'Amount'        => floatval($medsys_inventory->Amount) * -1,
            'UserID'        => $checkUser->idnumber,
            'DosageID'      => $medsys_inventory->DosageID,
            'RequestByID'   => $checkUser->idnumber,
            'DispenserCode' => $medsys_inventory->DispenserCode,
            'RequestNum'    => $medsys_inventory->RequestNum,
            'ListCost'      => isset($medsys_inventory->ListCost) 
                            ? $medsys_inventory->ListCost 
                            : null,
            'RecordStatus'  => 'R',
            'HostName'      => (new GetIP())->getHostname(),
        ];
    }

    private function CDGCashAssessmentData($request, $getCashAssessment, $checkUser) {
        return [
            'branch_id'             =>  1,
            'patient_id'            =>  $request->payload['patient_Id'],
            'case_No'               =>  $request->payload['case_No'],
            'patient_Name'          =>  $request->payload['patient_Name'] ?? $request->payload['patient_name'],
            'transdate'             =>  Carbon::now(),
            'assessnum'             =>  $getCashAssessment->assessnum,
            'drcr'                  =>  'C',
            'stat'                  =>  1,
            'revenueID'             =>  $getCashAssessment->revenueID,
            'refNum'                =>  $getCashAssessment->refNum,
            'itemID'                =>  $getCashAssessment->itemID,
            'item_ListCost'         =>  $getCashAssessment->item_ListCost,
            'item_Selling_Amount'   =>  $getCashAssessment->item_Selling_Amount,
            'item_OnHand'           =>  $getCashAssessment->item_OnHand,
            'quantity'              =>  intval($getCashAssessment->quantity) * -1,
            'amount'                =>  floatval($getCashAssessment->amount) * -1,
            'dosage'                =>  $getCashAssessment->dosage,
            'recordStatus'          =>  'R',
            'requestDescription'    =>  $getCashAssessment->requestDescription,
            'departmentID'          =>  $getCashAssessment->departmentID,
            'userId'                =>  $checkUser->idnumber,
            'hostname'              =>  (new GetIP())->getHostname(),
            'updatedBy'             =>  $checkUser->idnumber,
            'updated_at'            =>  Carbon::now(),
        ];
    }

    private function MedsysCashAssessmentData($request, $getMedsysCashAssessment, $checkUser) {
        return [
            'IdNum'         =>  $request->payload['case_No'] . 'B',
            'HospNum'       =>  $request->payload['patient_Id'],
            'Name'          =>  $request->payload['patient_Name'] ?? $request->payload['patient_name'],
            'TransDate'     =>  Carbon::now() ?? null,
            'AssessNum'     =>  $getMedsysCashAssessment->AssessNum,
            'Indicator'     =>  $getMedsysCashAssessment->Indicator,
            'DrCr'          =>  'D',
            'ItemID'        =>  $getMedsysCashAssessment->ItemID,
            'RecordStatus'  =>  'R',
            'Quantity'      =>  intval($getMedsysCashAssessment->Quantity) * -1,
            'RefNum'        =>  $getMedsysCashAssessment->RefNum,
            'Amount'        =>  floatval($getMedsysCashAssessment->Amount) * -1,
            'UserID'        =>  $checkUser->idnumber,
            'RevenueID'     =>  $getMedsysCashAssessment->RevenueID,
            'UnitPrice'     =>  $getMedsysCashAssessment->UnitPrice ? floatval($getMedsysCashAssessment->UnitPrice) : null,
        ];
    }

    private function BillingOutData($request,  $billingOut, $checkUser) {
        return [
            'patient_Id'            => $request->payload['patient_Id'],
            'case_No'               => $request->payload['case_No'],
            'accountnum'            => $billingOut->accountnum,
            'msc_price_scheme_id'   => $billingOut->msc_price_scheme_id,
            'revenueID'             => $billingOut->revenueID,
            'drcr'                  => 'C',
            'itemID'                => $billingOut->itemID,
            'quantity'              => ($billingOut->quantity * -1),
            'refNum'                => $billingOut->refNum . '[REVOKED]',
            'chargeSlip'            => $billingOut->ChargeSlip . '[REVOKED]',
            'amount'                => ($billingOut->amount * -1),
            'net_amount'            => ($billingOut->net_amount * -1),
            'userId'                => $checkUser->idnumber,
            'hostName'              => (new GetIP())->getHostname(),
            'updatedBy'             => $checkUser->idnumber,
            'updated_at'            => Carbon::now(),
            'request_doctors_id'    => $billingOut->request_doctors_id,
            'transDate'             => Carbon::now(),

        ];
    }

    private function MedsysBillingOutData($request,  $medsys_billingOut, $checkUser) {
        return [
            'IDNum'         => $request->payload['case_No'] . 'B',
            'HospNum'       => $request->payload['patient_Id'],
            'TransDate'     => Carbon::now(),
            'RevenueID'     => $medsys_billingOut->RevenueID,
            'DrCr'          => 'C',
            'ItenID'        => $medsys_billingOut->itemID,
            'Quantity'      => ($medsys_billingOut->Quantity * -1),
            'RefNum'        => $medsys_billingOut->RefNum . '[REVOKED]',
            'Amount'        => ($medsys_billingOut->Amount * -1),
            'UserID'        => $checkUser->idnumber,
            'ChargeSlip'    => $medsys_billingOut->ChargeSlip . '[REVOKED]',
            'HostName'      => (new GetIP())->getHostname(),
        ];
    }
}
