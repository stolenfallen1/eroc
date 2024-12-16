<?php

namespace App\Http\Controllers\HIS\his_functions\opd_specific;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\NurseCommunicationFile;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\MedSysCashAssessment;
use App\Models\HIS\medsys\MedSysDailyOut;
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

            $priceColumn = $request->patienttype == 1 ? 'item_Selling_Price_Out' : 'item_Selling_Price_In';
            $items = Itemmasters::with(['wareHouseItems' => function ($query) use ($request, $priceColumn) {
                $query->where('warehouse_Id', $request->warehouseID)
                        ->select('id', 'item_Id', 'item_OnHand', 'item_ListCost', DB::raw("$priceColumn as price"));
            }])
            ->whereHas('wareHouseItems', function ($query) use ($request) {
                $query->where('warehouse_Id', $request->warehouseID);
            })
            ->orderBy('item_name', 'asc');

            if($request->keyword) {
                $items->where('item_name','LIKE','%'.$request->keyword.'%');
            }
            if($request->itemcodes) {
                $items->whereNotIn('map_item_id', $request->itemcodes);
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
                    throw new \Exception('Error in getting MedSys Request Number and Reference Number');
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
                    $stat = $medicine['stat'] ?? null;
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
                        if ($this->check_is_allow_medsys):
                            MedSysCashAssessment::create([
                                'HospNum'           => $patient_Id,
                                'IdNum'             => $case_No . 'B',
                                'Name'              => $patient_Name,
                                'TransDate'         => $today,
                                'AssessNum'         => $assessnum_sequence,
                                'DrCr'              => 'C',
                                'ItemID'            => $itemID,
                                'Quantity'          => $requestQuantity,
                                'RefNum'            => $refNumSequence,
                                'ChargeSlip'        => $refNumSequence,
                                'Amount'            => $amount,
                                'Barcode'           => null,
                                'STAT'              => $stat,
                                'DoctorName'        => $doctor_name,
                                'UserID'            => Auth()->user()->idnumber,
                                'RevenueID'         => $revenueID,
                                'DepartmentID'      => $revenueID,
                            ]);
                        endif;
                    } else if ($charge_to == 'Company / Insurance') {
                        HISBillingOut::create([
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Type'              => 'O',
                            'transDate'                 => $today,
                            'msc_price_scheme_id'       => 2,
                            'revenueID'                 => $revenueID,
                            'itemID'                    => $itemID,
                            'quantity'                  => $requestQuantity,
                            'refNum'                    => $requestNum,
                            'ChargeSlip'                => $requestNum,
                            'amount'                    => $amount,
                            'userId'                    => Auth()->user()->idnumber,
                            'request_doctors_id'        => $doctor_Id,
                            'net_amount'                => $amount,
                            'hostName'                  => (new GetIP())->getHostname(),
                            'accountnum'                => $patient_Id,
                            'auto_discount'             => 0,
                            'createdby'                 => Auth()->user()->idnumber,
                            'created_at'                => $today,
                        ]);
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
                            'trasanction_Reference_Number'          => $referenceNum,
                            'transaction_Acctg_TransType'           => $revenueID,
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
                            MedSysDailyOut::create([
                                'Hospnum'                   => $patient_Id,
                                'IDNum'                     => $case_No . 'B',
                                'TransDate'                 => $today,
                                'RevenueID'                 => $revenueID,
                                'ItemID'                    => $itemID,
                                'Quantity'                  => $requestQuantity,
                                'RefNum'                    => $requestNum,
                                'ChargeSlip'                => $requestNum,
                                'Amount'                    => $amount,
                                'UserID'                    => Auth()->user()->idnumber,
                                'HostName'                  => (new GetIP())->getHostname(),
                                'AutoDiscount'              => 0,
                            ]);
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
                    $stat = $supplies['stat'] ?? null;
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
                        if ($this->check_is_allow_medsys):
                            MedSysCashAssessment::create([
                                'HospNum'           => $patient_Id,
                                'IdNum'             => $case_No . 'B',
                                'Name'              => $patient_Name,
                                'TransDate'         => $today,
                                'AssessNum'         => $assessnum_sequence,
                                'DrCr'              => 'C',
                                'ItemID'            => $itemID,
                                'Quantity'          => $requestQuantity,
                                'RefNum'            => $refNumSequence,
                                'ChargeSlip'        => $refNumSequence,
                                'Amount'            => $amount,
                                'Barcode'           => null,
                                'STAT'              => $stat,
                                'DoctorName'        => $doctor_name,
                                'UserID'            => Auth()->user()->idnumber,
                                'RevenueID'         => $revenueID,
                                'DepartmentID'      => $revenueID,
                            ]);
                        endif;
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
                            'trasanction_Reference_Number'          => $referenceNum,
                            'transaction_Acctg_TransType'           => $revenueID,
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
            $cashAssessmentResults = CashAssessment::where('patient_Id', $patient_Id)
                ->where('case_No', $case_No)
                ->whereNotNull('refNum')
                ->where(function($query) {
                    $query->where('issupplies', 1)
                        ->orWhere('ismedicine', 1);
                })
                ->whereRaw("refNum NOT LIKE '%\\[REVOKED\\]%' ESCAPE '\\'")
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($item) {
                    $item->source = 'CashAssessment'; 
                    return $item;
                });
    
            $nurseLogBookResults = NurseLogBook::where('patient_Id', $patient_Id)
                ->where('case_No', $case_No)
                ->where('record_Status', '!=', 'R')
                ->whereNotNull('requestNum')
                ->where(function($query) {
                    $query->where('issupplies', 1)
                        ->orWhere('ismedicine', 1);
                })
                ->whereRaw("requestNum NOT LIKE '%\\[REVOKED\\]%' ESCAPE '\\'")
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($item) {
                    $item->source = 'NurseLogBook';
                    return $item;
                });
    
            $cashAssessmentKeys = $cashAssessmentResults->pluck('refNum')->all();
            $filteredNurseLogBookResults = $nurseLogBookResults->filter(function ($item) use ($cashAssessmentKeys) {
                return !in_array($item->requestNum, $cashAssessmentKeys);
            });
    
            // Merge both results
            $combinedResults = $cashAssessmentResults->merge($filteredNurseLogBookResults);
    
            return $combinedResults;
        } catch (\Exception $e) {
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
                $item_Id = $item['item_Id'] ?? $item['itemID'];
                $requestNum = $item['requestNum'] ?? null;
                $referenceNum = $item['referenceNum'] ?? $item['refNum'];
                $charge_type = $item['charge_type'];
                $item_type = $item['item_type'];
                $source = $item['source'];

                if ($source == 'NurseLogBook') {
                    // Revoke Option for Nurse Log Book ( Company / Insurance )
                    $existingNurseLogs = NurseLogBook::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('item_Id', $item_Id)
                        ->where('requestNum', $requestNum)
                        ->where('referenceNum', $referenceNum)
                        ->first();

                    $existingNurseCommunicationFile = NurseCommunicationFile::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('item_Id', $item_Id)
                        ->where('requestNum', $requestNum)
                        ->where('referenceNum', $referenceNum)
                        ->first();

                    $existingInvestoryLogs = InventoryTransaction::where('patient_Id', $patient_Id)
                        ->where('patient_Registry_Id', $case_No)
                        ->where('transaction_Item_Id', $item_Id)
                        ->where('trasanction_Reference_Number', $referenceNum)
                        ->first();
    
                    $existingNurseLogs->update([
                        'record_Status' => 'R',
                        'requestNum' => $requestNum . '[REVOKED]',
                        'updatedat' => $today,
                        'updatedby' => Auth()->user()->idnumber,
                    ]);

                    $existingNurseCommunicationFile->update([
                        'record_Status' => 'R',
                        'requestNum' => $requestNum . '[REVOKED]',
                        'updatedat' => $today,
                        'updatedby' => Auth()->user()->idnumber,
                    ]);

                    $existingInvestoryLogs->updateOrFail([
                        'trasanction_Reference_Number' => $referenceNum . '[REVOKED]',
                        'updated_at' => $today,
                        'updatedBy' => Auth()->user()->idnumber,
                    ]);

                    if ($existingNurseLogs && $existingNurseCommunicationFile && $existingInvestoryLogs) {
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
                                    'RequestNum' => $requestNum . '[REVOKED]',
                                    'RecordStatus' => 'R',
                                ]);

                            tbNurseCommunicationFile::where('Hospnum', $patient_Id)
                                ->where('IDnum', $case_No . 'B')
                                ->where('ItemID', $item_Id)
                                ->where('RequestNum', $requestNum)
                                ->where('ReferenceNum', $referenceNum)
                                ->update([
                                    'RequestNum' => $requestNum . '[REVOKED]',
                                    'RecordStatus' => 'R',
                                ]);

                            $medSysInv = tbInvStockCard::where('Hospnum', $patient_Id)
                                ->where('IdNum', $case_No . 'B')
                                ->where('ItemID', $item_Id)
                                ->where('RefNum', $referenceNum)
                                ->first();
                            
                            $medSysInv->updateOrFail([
                                'RefNum' => $referenceNum . '[REVOKED]',
                            ]);

                            if ($medSysInv) {
                                tbInvStockCard::create([
                                    'SummaryCode'       => $item_type == 'Medicine' ? 'PH' : 'CS',
                                    'Hospnum'           => $medSysInv->Hospnum,
                                    'IdNum'             => $medSysInv->IdNum,
                                    'ItemID'            => $medSysInv->ItemID,
                                    'TransDate'         => Carbon::now(),
                                    'RevenueID'         => $item_type == 'Medicine' ? 'PH' : 'CS',
                                    'RefNum'            => $medSysInv->RefNum,
                                    'Quantity'          => $medSysInv->Quantity * -1,
                                    'Balance'           => $medSysInv->Balance,
                                    'NetCost'           => $medSysInv->NetCost * -1,
                                    'Amount'            => $medSysInv->Amount * -1,
                                    'UserID'            => Auth()->user()->idnumber,
                                    'RequestByID'       => Auth()->user()->idnumber,
                                    'LocationID'        => $medSysWarehouseID ?? null,
                                ]);
                            }

                        endif;
                    }

                } else if ($source == 'CashAssessment') {
                    // Revoke Option for Cash Assessment ( Self-Pay ) Since in the front-end you cannot revoke a charge that is already paid meaning nasulod nas NurseLogBook ang data
                    $existingCashAssessment = CashAssessment::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('itemID', $item_Id)
                        ->where('refNum', $referenceNum)
                        ->first();
                    
                    $existingCashAssessment->updateOrFail([
                        'dateRevoked' => Carbon::now(),
                        'revokedBy' => Auth()->user()->idnumber,
                        'updatedBy'     => Auth()->user()->idnumber,
                        'updated_at'    => Carbon::now(),
                        'refNum' => $referenceNum . '[REVOKED]',
                    ]);

                    if ($existingCashAssessment) {
                        CashAssessment::create([
                            'branch_id' => 1,
                            'patient_Id' => $existingCashAssessment->patient_Id,
                            'case_No' => $existingCashAssessment->case_No,
                            'patient_Name' => $existingCashAssessment->patient_Name,
                            'assessnum' => $existingCashAssessment->assessnum,
                            'transdate' => Carbon::now(),
                            'drcr' => 'C',
                            'revenueID' => $existingCashAssessment->revenueID,
                            'itemID' => $existingCashAssessment->itemID,
                            'quantity' => $existingCashAssessment->quantity * -1,
                            'refNum' => $existingCashAssessment->refNum,
                            'amount' => $existingCashAssessment->amount * -1,
                            'specimenId' => $existingCashAssessment->specimenId,
                            'requestDoctorID' => $existingCashAssessment->requestDoctorID,
                            'requestDoctorName' => $existingCashAssessment->requestDoctorName,
                            'departmentID' => $existingCashAssessment->departmentID,
                            'userId' => Auth()->user()->idnumber,
                            'Barcode' => null,
                            'hostname' => (new GetIP())->getHostname(),
                            'createdBy' => Auth()->user()->idnumber,
                            'created_at' => Carbon::now(),
                        ]);
                        if ($this->check_is_allow_medsys): 
                            MedSysCashAssessment::where('HospNum', $patient_Id)
                                ->where('IdNum', $case_No . 'B')
                                ->where('ItemID', $item_Id)
                                ->where('RefNum', $referenceNum)
                                ->update([
                                    'DateRevoked'   => Carbon::now(),
                                    'RevokedBy'     => Auth()->user()->idnumber,
                                    'RefNum'        => $referenceNum . '[REVOKED]',
                                    'Chargeslip'    => $referenceNum . '[REVOKED]',
                            ]);
                            MedSysCashAssessment::create([
                                'HospNum'           => $existingCashAssessment->patient_Id,
                                'IdNum'             => $existingCashAssessment->case_No . 'B',
                                'Name'              => $existingCashAssessment->patient_Name,
                                'TransDate'         => Carbon::now(),
                                'AssessNum'         => $existingCashAssessment->assessnum,
                                'DrCr'              => 'C',
                                'ItemID'            => $existingCashAssessment->itemID,
                                'Quantity'          => $existingCashAssessment->quantity * -1,
                                'RefNum'            => $existingCashAssessment->refNum,
                                'ChargeSlip'        => $existingCashAssessment->refNum,
                                'Amount'            => $existingCashAssessment->amount * -1,
                                'Barcode'           => null,
                                'STAT'              => null,
                                'DoctorName'        => $existingCashAssessment->requestDoctorName ?? null,
                                'UserID'            => Auth()->user()->idnumber,
                                'RevenueID'         => $existingCashAssessment->revenueID,
                                'DepartmentID'      => $existingCashAssessment->departmentID,
                            ]);
                        endif;
                    }

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
