<?php

namespace App\Http\Controllers\HIS\his_functions\opd_specific;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\NurseCommunicationFile;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\tbInvStockCard;
use App\Models\HIS\medsys\tbNurseCommunicationFile;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Helpers\GetIP;
use Auth;
use Carbon\Carbon;
use GlobalChargingSequences;
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
                $cashAssessmentSequence = new GlobalChargingSequences();
                $cashAssessmentSequence->incrementSequence(); 
                $assessnum_sequence = $cashAssessmentSequence->getSequence();
                $assessnum_sequence = $assessnum_sequence['MedSysCashSequence'];
                $inventoryChargeSlip = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->increment('DispensingCSlip');
                $nurseChargeSlip = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->increment('ChargeSlip');
                if ($inventoryChargeSlip && $nurseChargeSlip) {
                    $medSysRequestNum = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->value('ChargeSlip');
                    $medSysReferenceNum = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->value('DispensingCSlip');
                } else {
                    throw new \Exception("Failed to increment charge slips / transaction sequences");
                }
            } else {
                $assessnum_sequence = SystemSequence::where('code', 'GAN')->first();
            }

            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $patient_Name = $request->payload['patient_Name'];
            $doctor_Id = $request->payload['attending_Doctor'];
            $doctor_name = $request->payload['attending_Doctor_fullname'];
            $charge_to = $request->payload['charge_to'];

            if (isset($request->payload['Medicines']) && count($request->payload['Medicines']) > 0) {
                foreach ($request->payload['Medicines'] as $medicine) {
                    $revenueID = $medicine['code']; 
                    $warehouseID = $medicine['warehouse_id'];
                    // $warehouse_medsysID = $medicine['warehouse_medsys_id']; FOR USE LATER BUT THIS IS THE MEDSYS MAP_ITEM_ID 
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
                    $refNumSequence = $revenueID . $medSysReferenceNum;

                    if ($charge_to == 'Self-Pay') {
                        CashAssessment::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => 'O',
                            'transdate'                 => $today,
                            'assessnum'                 => $assessnum_sequence,
                            'drcr'                      => 'C',
                            'stat'                      => $stat,
                            'revenueID'                 => $revenueID,
                            'refNum'                    => $refNumSequence,
                            'itemID'                    => $itemID,
                            'item_ListCost'             => $item_list_cost,
                            'item_Selling_Amount'       => $price,
                            'item_OnHand'               => $item_OnHand,
                            'quantity'                  => $requestQuantity,
                            'amount'                    => $amount,
                            'section_Id'                => $warehouseID,
                            'dosage'                    => $frequency,
                            'requestDoctorID'           => $doctor_Id,
                            'requestDoctorName'         => $doctor_name,
                            'departmentID'              => $revenueID,
                            'userId'                    => Auth()->user()->idnumber,
                            'requestDescription'        => $item_name,
                            'ismedicine'                => 1,
                            'hostname'                  => (new GetIP())->getHostname(),
                            'createdBy'                 => Auth()->user()->idnumber,
                            'created_at'                => $today,
                        ]);
                    } else if ($charge_to == 'Company / Insurance') {
                        NurseLogBook::create([
                            'branch_Id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => 'O',
                            'revenue_Id'                => $revenueID,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'item_Id'                   => $itemID,
                            'description'               => $item_name,
                            'Quantity'                  => $requestQuantity,
                            'item_OnHand'               => $item_OnHand,
                            'item_ListCost'             => $item_list_cost,
                            'dosage'                    => $frequency,
                            'section_Id'                => $warehouseID,
                            'price'                     => $price,
                            'amount'                    => $amount,
                            'record_Status'             => 'W',
                            'user_Id'                   => Auth()->user()->idnumber,
                            'request_Date'              => $today,
                            'process_By'                => Auth()->user()->idnumber,
                            'process_Date'              => $today,
                            'stat'                      => $stat,
                            'ismedicine'                => 1,
                            'createdat'                 => $today,
                            'createdby'                 => Auth()->user()->idnumber,
                        ]);
                        NurseCommunicationFile::create([
                            'branch_Id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => 'O',
                            'item_Id'                   => $itemID,
                            'amount'                    => $amount,
                            'quantity'                  => $requestQuantity,
                            'dosage'                    => $frequency,
                            'section_Id'                => $warehouseID,
                            'request_Date'              => $today,
                            'revenue_Id'                => $revenueID,
                            'record_Status'             => 'W',
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'stat'                      => $stat,
                            'createdby'                 => Auth()->user()->idnumber,
                            'createdat'                 => $today,
                        ]);
                        InventoryTransaction::create([
                            'branch_Id'                             => 1,
                            'warehouse_Id'                          => $warehouseID,
                            'patient_Id'                            => $patient_Id,
                            'patient_Registry_Id'                   => $case_No,
                            'transaction_Item_Id'                   => $itemID,
                            'transaction_Date'                      => $today,
                            'transaction_Reference_Number'          => $referenceNum,
                            'transaction_Acctg_TransType'           => $revenueID,
                            'transaction_Acctg_Revenue_Code'        => $revenueID,
                            'transaction_Qty'                       => $requestQuantity,
                            'transaction_Item_OnHand'               => $item_OnHand,
                            'transaction_Item_ListCost'             => $item_list_cost,
                            'transaction_Item_SellingAmount'        => $price,
                            'transaction_Item_TotalAmount'          => $amount,
                            'transaction_Item_Med_Frequency_Id'     => $frequency,
                            'transaction_UserID'                    => Auth()->user()->idnumber,
                            'created_at'                            => $today,
                            'createdBy'                             => Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys): 
                            tbNurseLogBook::create([
                                'Hospnum'                   => $patient_Id,
                                'IDnum'                     => $case_No . 'B',
                                'PatientType'               => 'O',
                                'RevenueID'                 => $revenueID,
                                'RequestDate'               => $today,
                                'ItemID'                    => $itemID,
                                'Description'               => $item_name,
                                'Quantity'                  => $requestQuantity,
                                'Dosage'                    => $frequency,
                                'SectionID'                 => $warehouseID,
                                'Amount'                    => $amount,
                                'RecordStatus'              => 'W',
                                'UserID'                    => Auth()->user()->idnumber,
                                'ProcessBy'                 => Auth()->user()->idnumber,
                                'ProcessDate'               => $today,
                                'RequestNum'                => $requestNum,
                                'ReferenceNum'              => $referenceNum,
                                'Stat'                      => $stat,
                            ]);
                            tbNurseCommunicationFile::create([
                                'Hospnum'                   => $patient_Id,
                                'IDnum'                     => $case_No . 'B',
                                'PatientType'               => 'O',
                                'ItemID'                    => $itemID,
                                'Amount'                    => $amount,
                                'Quantity'                  => $requestQuantity,
                                'Dosage'                    => $frequency,
                                'SectionID'                 => $warehouseID,
                                'RequestDate'               => $today,
                                'RevenueID'                 => $revenueID,
                                'RecordStatus'              => 'W',
                                'UserID'                    => Auth()->user()->idnumber,
                                'RequestNum'                => $requestNum,
                                'ReferenceNum'              => $referenceNum,
                                'Remarks'                   => $remarks,
                                'Stat'                      => $stat == 1 ? 'N' : 'Y',
                            ]);
                            tbInvStockCard::create([
                                'SummaryCode'               => $revenueID,
                                'Hospnum'                   => $patient_Id,
                                'IdNum'                     => $case_No . 'B',
                                'ItemID'                    => $itemID,
                                'TransDate'                 => $today,
                                'RevenueID'                 => $revenueID,
                                'RefNum'                    => $referenceNum,
                                'Quantity'                  => $requestQuantity,
                                'Balance'                   => $item_OnHand,
                                'NetCost'                   => $item_list_cost,
                                'Amount'                    => $amount,
                                'UserID'                    => Auth()->user()->idnumber,
                                'DosageID'                  => $frequency,
                                'RequestByID'               => Auth()->user()->idnumber,
                            ]);
                        endif;
                    } else {
                        throw new \Exception('Invalid charge to');
                    }
                }
            }

            if (isset($request->payload['Supplies']) && count($request->payload['Supplies']) > 0) {
                foreach ($request->payload['Supplies'] as $supplies) {
                    $revenueID = $supplies['code']; 
                    $warehouseID = $supplies['warehouse_id'];
                    // $warehouse_medsysID = $medicine['warehouse_medsys_id']; FOR USE LATER BUT THIS IS THE MEDSYS MAP_ITEM_ID 
                    $item_OnHand = $supplies['item_OnHand'];
                    $itemID = $supplies['map_item_id'];
                    $item_name = $supplies['item_name'];
                    $requestQuantity = $supplies['quantity'];
                    $item_list_cost = $supplies['item_ListCost'];
                    $price = floatval(str_replace([',', '₱'], '', $supplies['price']));
                    $remarks = $supplies['remarks'];
                    $amount = floatval(str_replace([',', '₱'], '', $supplies['amount']));
                    $requestNum = $revenueID . $medSysRequestNum;
                    $referenceNum = 'C' . $medSysReferenceNum . 'M';
                    $refNumSequence = $revenueID . $medSysReferenceNum;

                    if ($charge_to == 'Self-Pay') {
                        CashAssessment::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => 'O',
                            'transdate'                 => $today,
                            'assessnum'                 => $assessnum_sequence,
                            'drcr'                      => 'C',
                            'revenueID'                 => $revenueID,
                            'refNum'                    => $refNumSequence,
                            'itemID'                    => $itemID,
                            'item_ListCost'             => $item_list_cost,
                            'item_Selling_Amount'       => $price,
                            'item_OnHand'               => $item_OnHand,
                            'quantity'                  => $requestQuantity,
                            'amount'                    => $amount,
                            'section_Id'                => $warehouseID,
                            'requestDoctorID'           => $doctor_Id,
                            'requestDoctorName'         => $doctor_name,
                            'departmentID'              => $revenueID,
                            'userId'                    => Auth()->user()->idnumber,
                            'requestDescription'        => $item_name,
                            'issupplies'                => 1,
                            'hostname'                  => (new GetIP())->getHostname(),
                            'createdBy'                 => Auth()->user()->idnumber,
                            'created_at'                => $today,
                        ]);
                    } else if ($charge_to == 'Company / Insurance') {
                        NurseLogBook::create([
                            'branch_Id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => 'O',
                            'revenue_Id'                => $revenueID,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'item_Id'                   => $itemID,
                            'description'               => $item_name,
                            'Quantity'                  => $requestQuantity,
                            'item_OnHand'               => $item_OnHand,
                            'item_ListCost'             => $item_list_cost,
                            'section_Id'                => $warehouseID,
                            'price'                     => $price,
                            'amount'                    => $amount,
                            'record_Status'             => 'W',
                            'user_Id'                   => Auth()->user()->idnumber,
                            'request_Date'              => $today,
                            'process_By'                => Auth()->user()->idnumber,
                            'process_Date'              => $today,
                            'stat'                      => $stat,
                            'issupplies'                => 1,
                            'createdat'                 => $today,
                            'createdby'                 => Auth()->user()->idnumber,
                        ]);
                        NurseCommunicationFile::create([
                            'branch_Id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => 'O',
                            'item_Id'                   => $itemID,
                            'amount'                    => $amount,
                            'quantity'                  => $requestQuantity,
                            'section_Id'                => $warehouseID,
                            'request_Date'              => $today,
                            'revenue_Id'                => $revenueID,
                            'record_Status'             => 'W',
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'createdby'                 => Auth()->user()->idnumber,
                            'createdat'                 => $today,
                        ]);
                        InventoryTransaction::create([
                            'branch_Id'                             => 1,
                            'warehouse_Id'                          => $warehouseID,
                            'patient_Id'                            => $patient_Id,
                            'patient_Registry_Id'                   => $case_No,
                            'transaction_Item_Id'                   => $itemID,
                            'transaction_Date'                      => $today,
                            'transaction_Reference_Number'          => $referenceNum,
                            'transaction_Acctg_TransType'           => $revenueID,
                            'transaction_Acctg_Revenue_Code'        => $revenueID,
                            'transaction_Qty'                       => $requestQuantity,
                            'transaction_Item_OnHand'               => $item_OnHand,
                            'transaction_Item_ListCost'             => $item_list_cost,
                            'transaction_Item_SellingAmount'        => $price,
                            'transaction_Item_TotalAmount'          => $amount,
                            'transaction_UserID'                    => Auth()->user()->idnumber,
                            'created_at'                            => $today,
                            'createdBy'                             => Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys): 
                            tbNurseLogBook::create([
                                'Hospnum'                   => $patient_Id,
                                'IDnum'                     => $case_No . 'B',
                                'PatientType'               => 'O',
                                'RevenueID'                 => $revenueID,
                                'RequestDate'               => $today,
                                'ItemID'                    => $itemID,
                                'Description'               => $item_name,
                                'Quantity'                  => $requestQuantity,
                                'SectionID'                 => $warehouseID,
                                'Amount'                    => $amount,
                                'RecordStatus'              => 'W',
                                'UserID'                    => Auth()->user()->idnumber,
                                'ProcessBy'                 => Auth()->user()->idnumber,
                                'ProcessDate'               => $today,
                                'RequestNum'                => $requestNum,
                                'ReferenceNum'              => $referenceNum,
                            ]);
                            tbNurseCommunicationFile::create([
                                'Hospnum'                   => $patient_Id,
                                'IDnum'                     => $case_No . 'B',
                                'PatientType'               => 'O',
                                'ItemID'                    => $itemID,
                                'Amount'                    => $amount,
                                'Quantity'                  => $requestQuantity,
                                'SectionID'                 => $warehouseID,
                                'RequestDate'               => $today,
                                'RevenueID'                 => $revenueID,
                                'RecordStatus'              => 'W',
                                'UserID'                    => Auth()->user()->idnumber,
                                'RequestNum'                => $requestNum,
                                'ReferenceNum'              => $referenceNum,
                                'Remarks'                   => $remarks,
                            ]);
                            tbInvStockCard::create([
                                'SummaryCode'               => $revenueID,
                                'Hospnum'                   => $patient_Id,
                                'IdNum'                     => $case_No . 'B',
                                'ItemID'                    => $itemID,
                                'TransDate'                 => $today,
                                'RevenueID'                 => $revenueID,
                                'RefNum'                    => $referenceNum,
                                'Quantity'                  => $requestQuantity,
                                'Balance'                   => $item_OnHand,
                                'NetCost'                   => $item_list_cost,
                                'Amount'                    => $amount,
                                'UserID'                    => Auth()->user()->idnumber,
                                'RequestByID'               => Auth()->user()->idnumber,
                            ]);
                        endif;
                    } else {
                        throw new \Exception('Invalid charge to');
                    }
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
