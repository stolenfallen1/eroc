<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Itemmasters;
use App\Models\MMIS\inventory\InventoryTransaction;
use DB;
use \Carbon\Carbon;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\tbInvStockCard;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\User;
use App\Helpers\GetIP;

class EmergencyRoomMedicine extends Controller
{
    //

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
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
    
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
            DB::connection('sqlsrv_mmis')->commit();

            return response()->json(['message' => 'Charge processing successful'], 200);

        } catch (Exception $e) {

            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();

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
    
            $tbNursePHSlipSequence = DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->first();
            $tbInvChargeSlipSequence = DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->first();

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
            $nurseLogBookData = $this->prepareNurseLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID);
            $inventoryTransactionData = $this->prepareInventoryTransactionData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $stock);
    
              /**************************************/
             /*            Insert Records          */
            /**************************************/
            tbNurseLogBook::create($tbNurseLogBookData);
            tbInvStockCard::create($tbInvStockCardData);
            NurseLogBook::create($nurseLogBookData);
            InventoryTransaction::create($inventoryTransactionData);
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
                'lb.Description', 
                'lb.Quantity', 
                'lb.Amount',
                'lb.Dosage', 
                'lb.RequestNum', 
                'lb.ReferenceNum', 
                'lb.RecordStatus',
                'sc.NetCost',
                'cdglb.description'
            )

            ->leftJoin('INVENTORY.dbo.tbInvStockCard as sc', function($join) {
                $join->on(
                            DB::raw("CASE WHEN RIGHT(lb.IDnum, 1) = 'B' THEN LEFT(lb.IDnum, LEN(lb.IDnum) - 1) ELSE lb.IDnum END"), '=', 
                            DB::raw("CASE WHEN RIGHT(sc.IdNum, 1) = 'B' THEN LEFT(sc.IdNum, LEN(sc.IdNum) - 1) ELSE sc.IdNum END")
                        )
                     ->on('lb.ItemID', '=', 'sc.ItemID');
            })

            ->leftJoin('CDG_PATIENT_DATA.dbo.NurseLogBook as cdglb', function($join) {
                $join->on(DB::raw("CASE WHEN RIGHT(lb.IDnum, 1) = 'B' THEN LEFT(lb.IDnum, LEN(lb.IDnum) - 1) ELSE lb.IDnum END"), '=', 'cdglb.case_No')
                     ->on('lb.ItemID', '=', 'cdglb.item_Id');
            })

            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('CDG_MMIS.dbo.inventoryTransaction as it')
                    ->whereColumn(DB::raw("CASE WHEN RIGHT(lb.IDnum, 1) = 'B' THEN LEFT(lb.IDnum, LEN(lb.IDnum) - 1) ELSE lb.IDnum END"), 'it.patient_Registry_Id')
                    ->whereColumn('lb.ItemID', 'it.transaction_Item_Id');
            })

            ->where(DB::raw("CASE WHEN RIGHT(lb.IDnum, 1) = 'B' THEN LEFT(lb.IDnum, LEN(lb.IDnum) - 1) ELSE lb.IDnum END"), $id)
            ->where('lb.RecordStatus', '!=', 'R')
            ->distinct() 
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
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

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
                'branch_Id'                     => 1,
                'warehouse_Group_Id'            => $cdg_mmis_inventory->warehouse_Group_Id,
                'warehouse_Id'                  => $cdg_mmis_inventory->warehouse_Id,
                'patient_Id'                    => $cdg_mmis_inventory->patient_Id,
                'patient_Registry_Id'           => $cdg_mmis_inventory->patient_Registry_Id,
                'transaction_Item_Id'           => $cdg_mmis_inventory->transaction_Item_Id,
                'transaction_Date'              => Carbon::now(),
                'trasanction_Reference_Number'  => $cdg_mmis_inventory->trasanction_Reference_Number,
                'transaction_Acctg_TransType'   => $cdg_mmis_inventory->transaction_Acctg_TransType,
                'transaction_Qty'               => (intval($cdg_mmis_inventory->transaction_Qty) * -1),
                'transaction_Item_OnHand'       => $cdg_mmis_inventory->transaction_Item_OnHand,
                'transaction_Item_ListCost'     => $cdg_mmis_inventory->transaction_Item_ListCost,
                'transaction_Requesting_Number' => $cdg_mmis_inventory->transaction_Requesting_Number,
                'transaction_UserId'            => $checkUser->idnumber,
                'created_at'                    => Carbon::now(),
                'createdBy'                     => $checkUser->idnumber,
                'updated_at'                    => Carbon::now(),
                'updatedby'                     => $checkUser->idnumber,
            ];

            $inventory_data = [
                'SummaryCode'                   => $inventory->SummaryCode,
                'HospNum'                       => $inventory->HospNum,
                'IdNum'                         => $inventory->IdNum,
                'ItemID'                        => $inventory->ItemID,
                'TransDate'                     => Carbon::now(),
                'RevenueID'                     => $inventory->RevenueID,
                'RefNum'                        => $inventory->RefNum,
                'Status'                        => $inventory->Status,
                'Quantity'                      => (intval($inventory->Quantity) * -1),
                'Balance'                       => $inventory->Balance,
                'NetCost'                       => $inventory->NetCost,
                'Amount'                        => (floatval($inventory->Amount) * -1),
                'UserID'                        => $checkUser->idnumber,
                'DosageID'                      => $inventory->DosageID,
                'RequestByID'                   => $checkUser->idnumber,
                'CreditMemoNum'                 => $inventory->CreditMemoNum,
                'DispenserCode'                 => $inventory->DispenserCode,
                'RequestNum'                    => $inventory->RequestNum,
                'ListCost'                      => $inventory->ListCost,
                'HostName'                      => (new GetIP())->getHostname(), 
            ];

            InventoryTransaction::create($cdg_mmis_inventory_data);
            tbInvStockCard::create($inventory_data);
            NurseLogBook::where('requestNum', $request->payload['reference_id'])->update(['record_Status' => 'R']);
            tbNurseLogBook::where('RequestNum', $request->payload['reference_id'])->update(['RecordStatus' => 'R']);

            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_inventory')->commit();
            DB::connection('sqlsrv_mmis')->commit();

            return response()->json([
                'message'   => 'Item Charged has been successfully Canceleled'
            ], 200);

        } catch(Exception $e) {

            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();

            return response()->json([
                'message' => 'Error!' . $e->getMessage()
            ], 500);
        }
    }
}
