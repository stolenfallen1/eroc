<?php

namespace App\Http\Controllers\HIS\his_functions\opd_specific;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Warehouseitems;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\tbInvStockCard;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\MMIS\inventory\InventoryTransaction;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OPDMedicinesSuppliesController extends Controller
{
    //
    protected $check_is_allow_medsys;
    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    public function medicineSuppliesList(Request $request) 
    {
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

            if ($request->itemcodes) {
                $items->whereNotIn('map_item_id', $request->itemcodes);  
            }

            if($request->keyword) {
                $items->where('item_name','LIKE','%'.$request->keyword.'%');
            }
            $page  = $request->per_page ?? '15';
            return response()->json($items->paginate($page), 200);

        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function chargeMedicineSupplies(Request $request) 
    {
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        try {
            if ($this->check_is_allow_medsys) {
                $inventoryChargeSlip = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->increment('DispensingCSlip');
                $nurseChargeSlip = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->increment('ChargeSlip');
                if ($inventoryChargeSlip && $nurseChargeSlip) {
                    $medSysRequestNum = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->value('DispensingCSlip');
                    $medSysReferenceNum = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->value('ChargeSlip');
                } else {
                    throw new \Exception("Failed to increment charge slips / transaction sequences");
                }
            } else {
                throw new \Exception('MedSys is not allowed, no sequence of our own tho.');
            }

            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $charge_to = $request->payload['charge_to'];

            if (isset($request->payload['Medicines']) && count($request->payload['Medicines']) > 0) {
                foreach ($request->payload['Medicines'] as $medicine) {
                    $revenueID = $medicine['code']; 
                    $warehouseID = $medicine['warehouse_id'];
                    $warehouse_medsysID = $medicine['warehouse_medsys_id'];
                    $frequency = $medicine['frequency'];
                    $item_OnHand = $medicine['item_OnHand'];
                    $itemID = $medicine['map_item_id'];
                    $item_name = $medicine['item_name'];
                    $requestQuantity = $medicine['quantity'];
                    $item_list_cost = $medicine['item_ListCost'];
                    $price = floatval(str_replace([',', '₱'], '', $medicine['price']));
                    $remarks = $medicine['remarks'];
                    $stat = $medicine['stat'];
                    $amount = floatval(str_replace([',', '₱'], '', $medicine['amount']));
                    $requestNum = $revenueID . $medSysRequestNum;
                    $referenceNum = 'C' . $medSysReferenceNum . 'M';

                    NurseLogBook::create([
                        'branch_Id'             => 1,
                        'patient_Id'            => $patient_Id,
                        'case_No'               => $case_No,
                        'patient_Type'          => 'O',
                        'revenue_Id'            => $revenueID,
                        'requestNum'            => $requestNum,
                        'referenceNum'          => $referenceNum,
                        'item_Id'               => $itemID,
                        'description'           => $item_name,
                        'Quantity'              => $requestQuantity,
                        'dosage'                => $frequency,
                        'amount'                => $amount,
                        'record_Status'         => 'X',
                        'remarks'               => $remarks,
                        'user_Id'               => Auth()->user()->idnumber,
                        'stat'                  => $stat,
                        'createdby'             => Auth()->user()->idnumber,
                        'createdat'             => $today,
                    ]);
                    InventoryTransaction::create([
                        'branch_Id'                         => 1,
                        'patient_Id'                        => $patient_Id,
                        'patient_Registry_Id'               => $case_No,
                        'warehouse_Id'                      => $warehouseID,
                        'transaction_Item_Id'               => $itemID,
                        'transaction_Date'                  => $today,
                        'trasanction_Reference_Number'      => $referenceNum,
                        'transaction_Acctg_TransType'       => $revenueID,
                        'transaction_Qty'                   => $requestQuantity,
                        'transaction_Item_OnHand'           => $item_OnHand,
                        'transaction_Item_ListCost'         => $item_list_cost,
                        'transaction_Item_SellingAmount'    => $price,
                        'transaction_Item_TotalAmount'      => $amount,
                        'transaction_Item_Med_Frequency_Id' => $frequency,
                        'transaction_Remarks'               => $remarks,
                        'transaction_UserID'                => Auth()->user()->idnumber,
                        'created_at'                        => $today,
                        'createdBy'                         => Auth()->user()->idnumber,
                    ]);
                    if ($this->check_is_allow_medsys): 
                        tbNurseLogBook::create([
                            'Hospnum'           => $patient_Id,
                            'IDnum'             => $case_No . 'B',
                            'PatientType'       => 'O',
                            'RevenueID'         => $revenueID,
                            'RequestDate'       => $today,
                            'itemID'            => $itemID,
                            'Description'       => $item_name,
                            'Quantity'          => $requestQuantity,
                            'Dosage'            => $frequency,
                            'Amount'            => $amount,
                            'RecordStatus'      => 'X',
                            'UserID'            => Auth()->user()->idnumber,
                            'RequestNum'        => $requestNum,
                            'ReferenceNum'      => $referenceNum,
                            'Remarks'           => $remarks,
                            'Stat'              => $stat,
                        ]);
                        tbInvStockCard::create([
                            'SummaryCode'       => 'PH',
                            'HospNum'           => $patient_Id,
                            'IdNum'             => $charge_to == 'Self-Pay' ? 'CASH' : $case_No . 'B',
                            'ItemID'            => $itemID,
                            'TransDate'         => $today,
                            'RevenueID'         => 'PH',
                            'RefNum'            => $referenceNum,
                            'Quantity'          => $requestQuantity,
                            'Balance'           => $item_OnHand,
                            'NetCost'           => $item_list_cost,
                            'Amount'            => $price,
                            'Remarks'           => $remarks,
                            'UserID'            => Auth()->user()->idnumber,
                            'RequestByID'       => Auth()->user()->idnumber,
                            'LocationID'        => $warehouse_medsysID,
                        ]);
                    endif;
                }
            }

            if (isset($request->payload['Supplies']) && count($request->payload['Supplies']) > 0) {
                foreach ($request->payload['Supplies'] as $supplies) {
                    $revenueID = $supplies['code']; 
                    $warehouseID = $supplies['warehouse_id'];
                    $warehouse_medsysID = $supplies['warehouse_medsys_id'];
                    $item_OnHand = $supplies['item_OnHand'];
                    $itemID = $supplies['map_item_id'];
                    $item_name = $supplies['item_name'];
                    $requestQuantity = $supplies['quantity'];
                    $item_list_cost = $supplies['item_ListCost'];
                    $price = floatval(str_replace([',', '₱'], '', $supplies['price']));
                    $remarks = $supplies['remarks'];
                    $stat = $supplies['stat'];
                    $amount = floatval(str_replace([',', '₱'], '', $supplies['amount']));
                    $requestNum = $revenueID . $medSysRequestNum;
                    $referenceNum = 'C' . $medSysReferenceNum . 'M';

                    NurseLogBook::create([
                        'branch_Id'             => 1,
                        'patient_Id'            => $patient_Id,
                        'case_No'               => $case_No,
                        'patient_Type'          => 'O',
                        'revenue_Id'            => $revenueID,
                        'requestNum'            => $requestNum,
                        'referenceNum'          => $referenceNum,
                        'item_Id'               => $itemID,
                        'description'           => $item_name,
                        'Quantity'              => $requestQuantity,
                        'amount'                => $amount,
                        'record_Status'         => 'X',
                        'remarks'               => $remarks,
                        'user_Id'               => Auth()->user()->idnumber,
                        'stat'                  => $stat,
                        'createdby'             => Auth()->user()->idnumber,
                        'createdat'             => $today,
                    ]);
                    InventoryTransaction::create([
                        'branch_Id'                         => 1,
                        'patient_Id'                        => $patient_Id,
                        'patient_Registry_Id'               => $case_No,
                        'warehouse_Id'                      => $warehouseID,
                        'transaction_Item_Id'               => $itemID,
                        'transaction_Date'                  => $today,
                        'trasanction_Reference_Number'      => $referenceNum,
                        'transaction_Acctg_TransType'       => $revenueID,
                        'transaction_Qty'                   => $requestQuantity,
                        'transaction_Item_OnHand'           => $item_OnHand,
                        'transaction_Item_ListCost'         => $item_list_cost,
                        'transaction_Item_SellingAmount'    => $price,
                        'transaction_Item_TotalAmount'      => $amount,
                        'transaction_Item_Med_Frequency_Id' => $frequency,
                        'transaction_Remarks'               => $remarks,
                        'transaction_UserID'                => Auth()->user()->idnumber,
                        'created_at'                        => $today,
                        'createdBy'                         => Auth()->user()->idnumber,
                    ]);
                    if ($this->check_is_allow_medsys):  
                        tbNurseLogBook::create([
                            'Hospnum'           => $patient_Id,
                            'IDnum'             => $case_No . 'B',
                            'PatientType'       => 'O',
                            'RevenueID'         => $revenueID,
                            'RequestDate'       => $today,
                            'itemID'            => $itemID,
                            'Description'       => $item_name,
                            'Quantity'          => $requestQuantity,
                            'Amount'            => $amount,
                            'RecordStatus'      => 'X',
                            'UserID'            => Auth()->user()->idnumber,
                            'RequestNum'        => $requestNum,
                            'ReferenceNum'      => $referenceNum,
                            'Remarks'           => $remarks,
                            'Stat'              => $stat,
                        ]);
                        tbInvStockCard::create([
                            'SummaryCode'       => 'CS',
                            'HospNum'           => $patient_Id,
                            'IdNum'             => $charge_to == 'Self-Pay' ? 'CASH' : $case_No . 'B',
                            'ItemID'            => $itemID,
                            'TransDate'         => $today,
                            'RevenueID'         => 'CS',
                            'RefNum'            => $referenceNum,
                            'Quantity'          => $requestQuantity,
                            'Balance'           => $item_OnHand,
                            'NetCost'           => $item_list_cost,
                            'Amount'            => $price,
                            'Remarks'           => $remarks,
                            'UserID'            => Auth()->user()->idnumber,
                            'RequestByID'       => Auth()->user()->idnumber,
                            'LocationID'        => $warehouse_medsysID,
                        ]);
                    endif;
                }
            }

            DB::connection('sqlsrv_medsys_inventory')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            $data = $this->history($patient_Id, $case_No);
            return response()->json(['message' => 'Charges posted successfully', 'data' => $data], 200);
            

        } catch(\Exception $e) {
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            return response()->json(["msg" => $e->getMessage()], 500); 
        }
    }
    public function getPostedMedicineSupplies(Request $request) 
    {
        try {
            $patient_Id = $request->patient_Id;
            $case_No = $request->case_No;
            $data = $this->history($patient_Id, $case_No);
            return response()->json(['data' => $data]);
        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function history($patient_Id, $case_No) 
    {
        try {
            $query = NurseLogBook::where('patient_Id', $patient_Id)
                ->where('case_No', $case_No)
                ->where('record_Status', '!=', 'R')
                ->whereNotNull('requestNum')
                ->orderBy('id', 'asc');

            return $query->get();
        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function revokecharge(Request $request) 
    {
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        try {
            $today = Carbon::now();
            $items = $request->items;
            foreach ($items as $item) {
                $patient_Id = $item['patient_Id'];
                $case_No = $item['case_No'];
                $item_Id = $item['item_Id'];
                $requestNum = $item['requestNum'];
                $referenceNum = $item['referenceNum'];
                $charge_type = $item['charge_type'];
                $item_type = $item['item_type'];

                $existingNurseLogs = NurseLogBook::where('patient_Id', $patient_Id)
                    ->where('case_No', $case_No)
                    ->where('item_Id', $item_Id)
                    ->where('requestNum', $requestNum)
                    ->where('referenceNum', $referenceNum)
                    ->first();

                $existingNurseLogs->update([
                    'record_Status' => 'R',
                    'updatedat' => $today,
                    'updatedby' => Auth()->user()->idnumber,
                ]);

                $existingInvestoryLogs = InventoryTransaction::where('patient_Id', $patient_Id)
                    ->where('patient_Registry_Id', $case_No)
                    ->where('transaction_Item_Id', $item_Id)
                    ->where('trasanction_Reference_Number', $referenceNum)
                    ->first();

                if ($existingNurseLogs && $existingInvestoryLogs) {
                    InventoryTransaction::create([
                        'branch_Id'                         => 1,
                        'warehouse_Id'                      => $existingInvestoryLogs->warehouse_Id,
                        'patient_Id'                        => $existingInvestoryLogs->patient_Id,
                        'patient_Registry_Id'               => $existingInvestoryLogs->patient_Registry_Id,
                        'transaction_Item_Id'               => $existingInvestoryLogs->transaction_Item_Id,
                        'transaction_Date'                  => $today,
                        'trasanction_Reference_Number'      => $existingInvestoryLogs->trasanction_Reference_Number,
                        'transaction_Acctg_TransType'       => $existingInvestoryLogs->transaction_Acctg_TransType,
                        'transaction_Qty'                   => $existingInvestoryLogs->transaction_Qty * -1,
                        'transaction_Item_OnHand'           => $existingInvestoryLogs->transaction_Item_OnHand,
                        'transaction_Item_ListCost'         => $existingInvestoryLogs->transaction_Item_ListCost * -1,
                        'transaction_Item_SellingAmount'    => $existingInvestoryLogs->transaction_Item_SellingAmount * -1,
                        'transaction_Item_TotalAmount'      => $existingInvestoryLogs->transaction_Item_TotalAmount * -1,
                        'transaction_Item_Med_Frequency_Id' => $existingInvestoryLogs->transaction_Item_Med_Frequency_Id,
                        'transaction_Remarks'               => 'Revoke Charge',
                        'transaction_UserID'                => Auth()->user()->idnumber,
                        'created_at'                        => $today,
                        'createdBy'                         => Auth()->user()->idnumber,
                    ]);
                    if ($this->check_is_allow_medsys): 

                        $medSysWarehouseID = DB::connection('sqlsrv')
                            ->table('CDG_CORE.dbo.fmsTransactionCodes')
                            ->where('warehouse_id', $existingInvestoryLogs->warehouse_Id)
                            ->value('warehouse_map_itemid');

                        tbNurseLogBook::where('Hospnum', $patient_Id)
                            ->where('IDnum', $case_No . 'B')
                            ->where('ItemID', $item_Id)
                            ->where('RequestNum', $requestNum)
                            ->where('ReferenceNum', $referenceNum)
                            ->update([
                                'RecordStatus' => 'R',
                            ]);

                        tbInvStockCard::create([
                            'SummaryCode'       => $item_type == 'Medicine' ? 'PH' : 'CS',
                            'Hospnum'           => $existingInvestoryLogs->patient_Id,
                            'IdNum'             => $charge_type == 'Self-Pay' ? 'CASH' : $existingInvestoryLogs->patient_Registry_Id . 'B',
                            'ItemID'            => $existingInvestoryLogs->transaction_Item_Id,
                            'TransDate'         => $today,
                            'RevenueID'         => $item_type == 'Medicine' ? 'PH' : 'CS',
                            'RefNum'            => $existingInvestoryLogs->trasanction_Reference_Number,
                            'Quantity'          => $existingInvestoryLogs->transaction_Qty * -1,
                            'Balance'           => $existingInvestoryLogs->transaction_Item_OnHand,
                            'NetCost'           => $existingInvestoryLogs->transaction_Item_ListCost * -1,
                            'Amount'            => $existingInvestoryLogs->transaction_Item_SellingAmount * -1,
                            'UserID'            => Auth()->user()->idnumber,
                            'RequestByID'       => Auth()->user()->idnumber,
                            'LocationID'        => $medSysWarehouseID ?? null,
                            'Remarks'           => 'Revoke Charge',
                        ]);
                    endif;
                }
            }
            DB::connection('sqlsrv_medsys_inventory')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            return response()->json(['message' => 'Charges revoked successfully'], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
