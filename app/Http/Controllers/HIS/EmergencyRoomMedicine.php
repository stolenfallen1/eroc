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

class EmergencyRoomMedicine extends Controller
{
    //
    protected $check_is_allow_medsys;
    public function __construct() {
        $this->isproduction = true;
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
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
              /**************************************/
             /*       Check user credentials       */
            /**************************************/
            $checkUser = User::where([
                ['idnumber', '=', $request->payload['user_userid']],
                ['passcode', '=', $request->payload['user_passcode']]
            ])->first();
    
            if (!$checkUser) {
                return response()->json(['message' => 'Incorrect Username or Password'], 404);
            }
    
              /**************************************/
             /*          Process Medicines         */ 
            /**************************************/
            if (isset($request->payload['Medicines']) && 
                count(array_filter($request->payload['Medicines'], function($item) {
                    return !empty($item['code']);
                })) > 0) {
                $this->processItems($request, $checkUser, 'Medicines');
            }

            if(isset($request->payload['itemListCost'])) {
                $this->processItems($request, $checkUser, 'itemListCost');
            }

              /**************************************/
             /*          Process Supplies          */
            /**************************************/
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
        DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->increment('ChargeSlip');
        DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->increment('DispensingCSlip');
        DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->increment('AssessmentNum');
        DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->increment('RequestNum');

        $tbNursePHSlipSequence = DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->first();
        $tbInvChargeSlipSequence = DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->first();
        $medsysCashAssessmentSequence = DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->first();
        
        foreach ($request->payload[$itemType] as $index => $item) {

            
              /********************************************/
             /*             Increment Sequences          */
            /********************************************/
            
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

              /**************************************/
             /*     Prepare data for insertion     */
            /**************************************/
            $tbNurseLogBookData = $this->prepareMedsysLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID);
            $tbInvStockCardData = $this->prepareStockCardData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $listCost);
            $cashAssessment = $this->prepareCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence);
            $tbCashAssessment = $this->prepareMedsysCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence);
            $nurseLogBookData = $this->prepareNurseLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID);
            $inventoryTransactionData = $this->prepareInventoryTransactionData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $stock);
    
              /**************************************/
             /*            Insert Records          */
            /**************************************/
            if($request->payload['charge_to'] === 'Company / Insurance') {
                tbNurseLogBook::create($tbNurseLogBookData);
                NurseLogBook::create($nurseLogBookData);
                InventoryTransaction::create($inventoryTransactionData);
                tbInvStockCard::create($tbInvStockCardData);
            } else {
                CashAssessment::create($cashAssessment);
                MedsysCashAssessment::create($tbCashAssessment);
            }
        }
    }
    
    private function prepareMedsysLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID) {
        return [
            'Hospnum'       => $request->payload['patient_Id'] ?? null,
            'IDnum'         => $request->payload['case_No'] . 'B' ?? null,
            'PatientType'   => $request->payload[''] ?? null,
            'RevenueID'     => $item['code'],
            'RequestDate'   => Carbon::now(),
            'ItemID'        => $itemID,
            'Description'   => $item['item_name'] ?? null,
            'Quantity'      => $item['quantity'] ?? null,
            'Dosage'        => $item['frequency'] ?? null,
            'Amount'        => $item['amount'],
            'RecordStatus'  => 'W',
            'UserID'        => $checkUser->idnumber,
            'ProcessBy'     => $checkUser->idnumber,
            'ProcessDate'   => Carbon::now(),
            'Remarks'       => $item['remarks'] ?? null,
            'RequestNum'    => $tbNursePHSlipSequence->ChargeSlip,
            'ReferenceNum'  => $tbInvChargeSlipSequence->DispensingCSlip,
            'Stat'          => $item['stat'] ?? null,
            'dcrdate'       => $request->payload['dcrdate'] ?? null,
            'isGeneric'     => 0,
            'AMPickup'      => 0,
        ];
    }
    
    private function prepareStockCardData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $listCost) {
        $item_price =  $item['price'] = str_replace('₱', '', $item['price']);
        return [
            'SummaryCode'   => $item['code'],
            'HospNum'       => $request->payload['patient_Id'] ?? null,
            'IdNum'         => $request->payload['case_No'] . 'B' ?? null,
            'ItemID'        => $itemID,
            'TransDate'     => Carbon::now(),
            'RevenueID'     => $item['code'] ?? null,
            'RefNum'        => $tbInvChargeSlipSequence->DispensingCSlip,
            'Status'        => $item['stat'] ?? null,
            'Quantity'      => $item['quantity'] ?? null,
            'Balance'       => $request->payload['Balance'] ?? null,
            'NetCost'       => $item_price ? floatval($item_price) : null,
            'Amount'        => $item['amount'] ?? null,
            'UserID'        => $checkUser->idnumber,
            'DosageID'      => $item['frequency'] ?? null,
            'RequestByID'   => $checkUser->idnumber,
            'CreditMemoNum' => $request->payload['CreditMemoNum'] ?? null,
            'DispenserCode' => 0,
            'RequestNum'    => $tbNursePHSlipSequence->ChargeSlip,
            'ListCost'      => $listCost,
            'RecordStatus'  => 'W',
            'HostName'      => (new GetIP())->getHostname(),
        ];
    }

    private function prepareCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence) {
        return [
            'branch_id'             => 1,
            'patient_id'            => $request->payload['patient_Id'],
            'case_No'               => $request->payload['case_No'],
            'patient_Name'          => $request->payload['patient_Name'],
            'transdate'             => Carbon::now(),
            'assessNum'             => intval($medsysCashAssessmentSequence->RequestNum),
            'Indicator'             => $item['code'],
            'drcr'                  => 'C',
            'stat'                  => 1,
            'revenueID'             => $item['code'],
            'refNum'                => $item['code'] . intval($medsysCashAssessmentSequence->AssessmentNum),
            'itemID'                => $itemID,
            'quantity'              => $item['quantity'],
            'amount'                => $item['amount'],
            'specimenId'            => '',
            'dosage'                => $item['frequency'] ?? null,
            'recordStatus'          => 'X',
            'departmentID'          => 'ER',
            'requestDescription'    => $item['item_name'],
            'requestDoctorID'       => '',
            'requestDoctorName'     => '',
            'hostname'              => (new GetIP())->getHostname(),
            'createdBy'             => $checkUser->idnumber,
            'created_at'            => Carbon::now(),
        ];
    }

    private function prepareMedsysCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence) {
        $item_price =  $item['price'] = str_replace('₱', '', $item['price']);
        return [
            'IdNum'         => $request->payload['case_No'] . 'B',
            'HospNum'       => $request->payload['patient_Id'],
            'Name'          => $request->payload['patient_Name'],
            'TransDate'     => Carbon::now() ?? null,
            'AssessNum'     => intval($medsysCashAssessmentSequence->RequestNum),
            'Indicator'     => $item['code'],
            'DrCr'          => 'D',
            'RecordStatus'  => 'X',
            'ItemID'        => $itemID,
            'Quantity'      => $item['quantity'],
            'RefNum'        => $item['code'] . intval($medsysCashAssessmentSequence->AssessmentNum),
            'Amount'        => $item['amount'],
            'UserID'        => $checkUser->idnumber,
            'RevenueID'     => $item['code'],
            'DepartmentID'  => 'ER',
            'UnitPrice'     => $item_price ? floatval($item_price) : null,
        ];
    }
    
    private function prepareNurseLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID) {
        return [
            'branch_Id'        => 1,
            'patient_Id'       => $request->payload['patient_Id'],
            'case_No'          => $request->payload['case_No'],
            'patient_Type'     => 0,
            'revenue_Id'       => $item['code'],
            'requestNum'       => $tbNursePHSlipSequence->ChargeSlip,
            'referenceNum'     => $tbInvChargeSlipSequence->DispensingCSlip,
            'item_Id'          => $itemID,
            'description'      => $item['item_name'] ?? null,
            'specimen_Id'      => $request->payload['specimen_Id'] ?? null,
            'Quantity'         => $item['quantity'] ?? null,
            'dosage'           => $item['frequency'] ?? null,
            'section_Id'       => $request->payload['section_Id'] ?? null,
            'amount'           => $item['amount'] ?? null,
            'record_Status'    => 'W',
            'user_Id'          => $checkUser->idnumber,
            'remarks'          => $item['remarks'] ?? null,
            'isGeneric'        => 0,
            'isMajorOperation' => 0,
            'createdat'        => Carbon::now(),
            'createdby'        => $checkUser->idnumber,
        ];
    }
    
    private function prepareInventoryTransactionData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $stock) {
        return [
            'branch_Id'                     => 1,
            'warehouse_Group_Id'            => $request->payload['warehouse_Group_Id'] ?? null,
            'warehouse_Id'                  => $request->payload['warehouse_Id'] ?? null,
            'patient_Id'                    => $request->payload['patient_Id'] ?? null,
            'patient_Registry_Id'           => $request->payload['case_No'] ?? null,
            'transaction_Item_Id'           => $itemID,
            'transaction_Date'              => Carbon::now(),
            'trasanction_Reference_Number'  => $tbInvChargeSlipSequence->DispensingCSlip,
            'transaction_Acctg_TransType'   => $item['code'] ?? null,
            'transaction_Qty'               => $item['quantity'] ?? null,
            'transaction_Item_OnHand'       => $stock,
            'transaction_Item_ListCost'     => $request->payload['transaction_Item_ListCost'] ?? null,
            'transaction_Requesting_Number' => $tbNursePHSlipSequence->ChargeSlip,
            'transaction_UserId'            => $checkUser->idnumber,
            'created_at'                    => Carbon::now(),
            'createdBy'                     => $checkUser->idnumber,
            'updated_at'                    => Carbon::now(),
            'updatedby'                     => $checkUser->idnumber,
        ];
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
                    'mscD.description as frequency'
                )
                ->leftJoin('CDG_CORE.dbo.mscDosages as mscD', 'ca.dosage', '=', 'mscD.dosage_id')
                ->where('ca.case_No', '=', $id)
                ->where('ca.recordStatus', '!=', '27')
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
                    'revenue_Id'    => $item->revenue_Id                    ?? $item->revenueID,
                    'record_Status' => $item->record_Status                 ?? $item->recordStatus,
                    'item_Id'       => $item->item_Id                       ?? $item->itemID,
                    'description'   => (isset($item->requestDescription) 
                                    ? $item->requestDescription 
                                    : $item->description                    ?? null),
                    'price'         => $item->price                         ?? null,
                    'Quantity'      => $item->Quantity                      ?? $item->quantity,
                    'dosage'        => $item->dosage                        ?? $item->dosage,
                    'amount'        => $item->amount                        ?? $item->amount,
                    'referenceNum'  => $item->referenceNum                  ?? $item->refNum,
                    'assessnum'     => $item->assessnum                     ?? null,
                    'frequency'     => $item->frequency                     ?? null
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
    
    
    public function cancelCharges(Request $request) {

        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();

        try{
            $checkUser = User::where([
                ['idnumber', '=', $request->payload['user_userid']],
                ['passcode', '=', $request->payload['user_passcode']]
            ])->first();

            if (!$checkUser) {
                return response()->json(['message' => 'Incorrect Username or Password'], 404);
            }

            if($request->payload['charge_to'] === 'Company / Insurance') {
                $cdg_mmis_inventory = InventoryTransaction::where('trasanction_Reference_Number', $request->payload['reference_id'])->first();
                $medsys_inventory = tbInvStockCard::where('RefNum', $request->payload['reference_id'])->first();

                $cdg_mmis_inventory_data = [
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

                $medsys_inventory_data = [
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

                $isCreated_cdg_MMIS                 = InventoryTransaction::create($cdg_mmis_inventory_data);
                $isCreated_medsys_Inventory         = tbInvStockCard::create($medsys_inventory_data);
                $isUpdated_cdg_lb                   = NurseLogBook::where('referenceNum', $request->payload['reference_id'])->update(['record_Status' => 'R']);
                $isUpdated_cdg_medsys_lb            = tbNurseLogBook::where('ReferenceNum', $request->payload['reference_id'])->update(['RecordStatus' => 'R']);

                if(!$isCreated_cdg_MMIS || !$isCreated_medsys_Inventory || !$isUpdated_cdg_lb || !$isUpdated_cdg_medsys_lb) {
                
                    throw new Exception('Failed to cancel charges');
                }

            } else {
                $getCashAssessment = CashAssessment::where('RefNum', $request->payload['reference_id'])->first();
                $getMedsysCashAssessment = MedsysCashAssessment::where('RefNum', $request->payload['reference_id'])->first();

                $cdg_cash_assessment_data = [
                    'branch_id'             => 1,
                    'patient_id'            => $request->payload['patient_Id'],
                    'case_No'               => $request->payload['case_No'],
                    'patient_Name'          => $request->payload['patient_Name'],
                    'transdate'             => Carbon::now(),
                    'assessnum'             => $getCashAssessment->assessnum,
                    'drcr'                  => 'C',
                    'stat'                  => 1,
                    'revenueID'             =>  $getCashAssessment->revenueID,
                    'refNum'                =>  $getCashAssessment->refNum,
                    'itemID'                =>  $getCashAssessment->itemID,
                    'quantity'              =>  intval($getCashAssessment->quantity) * -1,
                    'amount'                => floatval($getCashAssessment->amount) * -1,
                    'requestDescription'    => $getCashAssessment->requestDescription,
                    'hostname'              => (new GetIP())->getHostname(),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => Carbon::now(),
                ];
    
                $medsys_cash_assessment_data = [
                    'IdNum'         =>  $request->payload['case_No'] . 'B',
                    'HospNum'       =>  $request->payload['patient_Id'],
                    'Name'          =>  $request->payload['patient_Name'],
                    'TransDate'     =>  Carbon::now() ?? null,
                    'AssessNum'     =>  $getMedsysCashAssessment->AssessNum,
                    'Indicator'     =>  $getMedsysCashAssessment->Indicator,
                    'DrCr'          =>  'D',
                    'ItemID'        =>  $getMedsysCashAssessment->ItemID,
                    'Quantity'      =>  intval($getMedsysCashAssessment->Quantity) * -1,
                    'RefNum'        =>  $getMedsysCashAssessment->RefNum,
                    'Amount'        =>  floatval($getMedsysCashAssessment->Amount) * -1,
                    'UserID'        =>  $checkUser->idnumber,
                    'RevenueID'     =>  $getMedsysCashAssessment->RevenueID,
                    'UnitPrice'     =>  $getMedsysCashAssessment->UnitPrice ? floatval($getMedsysCashAssessment->UnitPrice) : null,
                ];

               $isUpdatedRow = CashAssessment::where('refNum', $request->payload['reference_id'])->update([
                    'recordStatus'  => 'R',
                    'dateRevoked'   => Carbon::now(),
                    'revokedBy'     => $checkUser->idnumber,
                ]);

                $isRowCreated = CashAssessment::create( $cdg_cash_assessment_data);

                $isMedysUpdatedRow = MedsysCashAssessment::where('RefNum', $request->payload['reference_id'])->update([
                    'RecordStatus'  => 'R',
                    'DateRevoked'   => Carbon::now(),
                    'RevokedBy'     => $checkUser->idnumber
                ]);

                $isMedsysRowCreated = MedsysCashAssessment::create($medsys_cash_assessment_data);

                if(!$isUpdatedRow || !$isRowCreated || !$isMedysUpdatedRow || !$isMedsysRowCreated) {
                    throw new Exception('Failed to cancel charges');
                }
            }
        
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();

            return response()->json([
                'message'   => 'Item Charged has been successfully Canceleled'
            ], 200);

        } catch(Exception $e) {

            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();

            return response()->json([
                'message' => 'Error!' . $e->getMessage()
            ], 500);
        }
    }
}
