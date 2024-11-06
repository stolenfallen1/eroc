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
        
        foreach ($request->payload[$itemType] as $index => $item) {

            
              /********************************************/
             /*             Increment Sequences          */
            /********************************************/
            DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->increment('ChargeSlip');
            DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->increment('DispensingCSlip');
            DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->increment('AssessmentNum');
            DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->increment('RequestNum');

            $tbNursePHSlipSequence = DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->first();
            $tbInvChargeSlipSequence = DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->first();
            $medsysCashAssessmentSequence = DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->first();
            
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
            $tbNurseLogBookData = $this->prepareLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID);
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
    
    private function prepareLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID) {
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
            'RecordStatus'  => $request->payload['RecordStatus'] ?? 'X',
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
            'HostName'      => (new GetIP())->getHostname(),
        ];
    }

    private function prepareCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence) {
        return [
            'branch_id'     => 1,
            'patient_id'    => $request->payload['patient_Id'],
            'case_No'       => $request->payload['case_No'],
            'patient_Name'  => $request->payload['patient_Name'],
            'transdate'     => Carbon::now(),
            'assessNum'     => intval($medsysCashAssessmentSequence->RequestNum),
            'drcr'          => 'C',
            'stat'          => 1,
            'revenueID'     => $item['code'],
            'refNum'        => $item['code'] . intval($medsysCashAssessmentSequence->AssessmentNum),
            'itemID'        => $itemID,
            'quantity'      => $item['quantity'],
            'amount'        => $item['amount'],
            'specimenId'    => '',
            'recordStatus'  => '',
            'requestDoctorID' => '',
            'requestDoctorName' => '',
            'hostname'          => (new GetIP())->getHostname(),
            'createdBy'         => $checkUser->idnumber,
            'created_at'        => Carbon::now(),
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
            'RecordStatus'  => '1',
            'ItemID'        => $itemID,
            'Quantity'      => $item['quantity'],
            'RefNum'        => $item['code'] . intval($medsysCashAssessmentSequence->AssessmentNum),
            'Amount'        => $item['amount'],
            'UserID'        => $checkUser->idnumber,
            'RevenueID'     => $item['code'],
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
            'description'      => $item['frequency_description'] ?? null,
            'specimen_Id'      => $request->payload['specimen_Id'] ?? null,
            'Quantity'         => $item['quantity'] ?? null,
            'dosage'           => $item['frequency'] ?? null,
            'section_Id'       => $request->payload['section_Id'] ?? null,
            'amount'           => $item['amount'] ?? null,
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
        $data = DB::table('STATION.dbo.tbNurseLogBook as lb')
            ->select(
                'lb.Hospnum', 
                'lb.IDnum', 
                'lb.RevenueID', 
                'lb.ItemID', 
                DB::raw('MIN(lb.Description) as Description'),
                'lb.Quantity', 
                'lb.Amount',
                DB::raw('MIN(lb.Dosage) as Dosage'),
                'lb.RequestNum', 
                'lb.ReferenceNum', 
                'lb.RecordStatus',
                DB::raw('MIN(mca.UnitPrice) as UnitPrice'),
                DB::raw('MIN(mca.AssessNum) as AssessNum'),
                DB::raw('MIN(cdglb.description) as cdglb_description')
            )
    
            ->leftJoin(DB::raw(
                    "(SELECT 
                        IdNum, 
                        ItemID, 
                        MIN(UnitPrice) as UnitPrice, 
                        MIN(AssessNum) as AssessNum 
                    FROM BILLING.dbo.tbCashAssessment 
                    GROUP BY IdNum, ItemID) as mca"
                ), 

                function($join) {
                    $join->on('lb.IDnum', '=', 'mca.IdNum')
                        ->on('lb.ItemID', '=', 'mca.ItemID');
                }
            )
    
            ->leftJoin(DB::raw(
                    "(SELECT 
                        case_No, 
                        item_Id, 
                        MIN(description) as description 
                    FROM CDG_PATIENT_DATA.dbo.NurseLogBook 
                    GROUP BY case_No, item_Id) as cdglb"
                ), 
                
                function($join) {
                    $join->on(DB::raw(
                        "
                            CASE WHEN RIGHT(lb.IDnum, 1) = 'B' 
                            THEN LEFT(lb.IDnum, LEN(lb.IDnum) - 1) 
                            ELSE lb.IDnum END
                        "
                    ), '=', 'cdglb.case_No')
                    ->on('lb.ItemID', '=', 'cdglb.item_Id');
                }
            )
    
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('CDG_MMIS.dbo.inventoryTransaction as it')
                    ->whereColumn(DB::raw(
                        "
                            CASE WHEN RIGHT(lb.IDnum, 1) = 'B' 
                            THEN LEFT(lb.IDnum, LEN(lb.IDnum) - 1) 
                            ELSE lb.IDnum END
                        "
                    ), 'it.patient_Registry_Id')
                ->whereColumn('lb.ItemID', 'it.transaction_Item_Id');
            })
    
            ->where(DB::raw(
                "
                    CASE WHEN RIGHT(lb.IDnum, 1) = 'B' 
                    THEN LEFT(lb.IDnum, LEN(lb.IDnum) - 1) 
                    ELSE lb.IDnum END
                "
            ), $id)

            ->where('lb.RecordStatus', '!=', 'R')
            ->groupBy(
                'lb.Hospnum', 
                'lb.IDnum', 
                'lb.RevenueID', 
                'lb.ItemID', 
                'lb.Quantity', 
                'lb.Amount', 
                'lb.RequestNum', 
                'lb.ReferenceNum', 
                'lb.RecordStatus'
            )
            ->get();
    
        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'No Charges'
            ], 404);
        }
    
        return response()->json($data, 200);
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

            $cdg_mmis_inventory = InventoryTransaction::where('transaction_Requesting_Number', $request->payload['reference_id'])->first();
            $inventory = tbInvStockCard::where('RequestNum', $request->payload['reference_id'])->first();

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

            $isCreated_cdg_MMIS                 = InventoryTransaction::create($cdg_mmis_inventory_data);
            $isUpdated_cdg_lb                   = NurseLogBook::where('requestNum', $request->payload['reference_id'])->update(['record_Status' => 'R']);
            $isUpdated_cdg_medsys_lb            = tbNurseLogBook::where('RequestNum', $request->payload['reference_id'])->update(['RecordStatus' => 'R']);
            $isUpdated_medsys_cash_assessment   = MedsysCashAssessment::where('AssessNum', $request->payload['medsys_AssessNum'])->update(['RecordStatus' => 0]);
            
            if(!$isCreated_cdg_MMIS || !$isUpdated_medsys_cash_assessment || !$isUpdated_cdg_lb || !$isUpdated_cdg_medsys_lb) {
                
                throw new Exception('Failed to cancel charges');
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
