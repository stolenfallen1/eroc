<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Itemmasters;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\MedSysCashAssessment;
use App\Models\HIS\medsys\tbNurseLogBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use GlobalChargingSequences;
use App\Helpers\GetIP;
use App\Models\User;

class RequisitionController extends Controller
{
    //
    protected $check_is_allow_medsys;
    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    public function getWarehouses() 
    {
        try {
            if (!Request()->category) throw new \Exception("Category is required");
    
            $warehouses = TransactionCodes::query();
    
            if (Request()->category === 'supply') {
                $warehouses->where('isSupplies', 1);
                $warehouses->where('warehouse_id', '!=', 0); 
                $warehouses->orderByRaw("CASE WHEN description = 'Central Supply' THEN 0 ELSE 1 END");
            } elseif (Request()->category === 'medicine') {
                $warehouses->where('isMedicine', 1);
                $warehouses->where('warehouse_id', '!=', 0); 
                $warehouses->orderByRaw("CASE WHEN description = 'Pharmacy' THEN 0 ELSE 1 END");
            } elseif (Request()->category === 'procedure') {
                $warehouses->where('isProcedure', 1);
            } else {
                throw new \Exception("Invalid category specified");
            }
    
            $warehouses->where('isActive', 1);
            $warehouses->orderBy('description', 'asc');
    
            if(Request()->keyword) {
                $warehouses->where(function($subQuery) {
                    $subQuery->where('description', 'LIKE', '%'.Request()->keyword.'%');
                });
            }
    
            $page = Request()->per_page ?? 10;
            return response()->json($warehouses->paginate($page), 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getWarehouseItems(Request $request) 
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
            $page  = $request->per_page ?? '15';
            return response()->json($items->paginate($page), 200);

        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function getPatientRequisitions(Request $request) 
    {
        try {
            $patient_Id = $request->items['patient_Id'];
            $case_No = $request->items['case_No'];
            $account = $request->items['account'];
            $data = []; 
            if ($account == 1) {
                $cashAssessments = CashAssessment::where('patient_Id', $patient_Id)
                    ->where('case_No', $case_No)
                    ->where('recordStatus', 27) // Pending or unpaid
                    ->where(function($query) {
                        $query->where('issupplies', 1)
                                ->orWhere('ismedicine', 1)
                                ->orWhere('isprocedure', 1);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
    
                // Add CashAssessment data to the response
                foreach ($cashAssessments as $item) {
                    $data[] = [
                        'patient_Id' => $item->patient_Id,
                        'case_No' => $item->case_No,
                        'recordStatus' => $item->recordStatus,
                        'isUnpaid' => true, 
                        'details' => $item, 
                    ];
                }
    
                $nurseLogEntries = NurseLogBook::where('patient_Id', $patient_Id)
                    ->where('case_No', $case_No)
                    ->where('record_Status', 'X') // Not carried order
                    ->where(function($query) {
                        $query->where('issupplies', 1)
                                ->orWhere('ismedicine', 1)
                                ->orWhere('isprocedure', 1);
                    })
                    ->orderBy('createdat', 'desc')
                    ->get();
    
                foreach ($nurseLogEntries as $item) {
                    $data[] = [
                        'patient_Id' => $item->patient_Id,
                        'case_No' => $item->case_No,
                        'recordStatus' => $item->recordStatus,
                        'isUnpaid' => false,  
                        'details' => $item,  
                    ];
                }
            } 
    
            else if ($account == 2) {
                $nurseLogEntries = NurseLogBook::where('patient_Id', $patient_Id)
                    ->where('case_No', $case_No)
                    ->where('record_Status', 'X') // Not carried order
                    ->where(function($query) {
                        $query->where('issupplies', 1)
                                ->orWhere('ismedicine', 1)
                                ->orWhere('isprocedure', 1);
                    })
                    ->orderBy('createdat', 'desc')
                    ->get();
    
                foreach ($nurseLogEntries as $item) {
                    $data[] = [
                        'patient_Id' => $item->patient_Id,
                        'case_No' => $item->case_No,
                        'recordStatus' => $item->recordStatus,
                        'isUnpaid' => false,  
                        'details' => $item, 
                    ];
                }
            }
    
            return response()->json([
                "data" => $data
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function saveSupplyRequisition(Request $request) 
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        try {
            $checkUser = null;
            if (isset($request->payload['user_userid']) && isset($request->payload['user_passcode'])) {
                $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
                if (!$checkUser) {
                    return response()->json([
                        'message' => 'Incorrect Username or Password',
                    ], 404);
                }
            }

            if ($this->check_is_allow_medsys) {
                $cashAssessmentSequence = new GlobalChargingSequences();
                $cashAssessmentSequence->incrementSequence(); 
                $assessnum_sequence = $cashAssessmentSequence->getSequence();
                $assessnum_sequence = $assessnum_sequence['MedSysCashSequence'];
                $inventoryChargeSlip = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->increment('DispensingCSlip');
                $nurseChargeSlip = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->increment('ChargeSlip');
                if ($inventoryChargeSlip && $nurseChargeSlip) {
                    $medSysRequestNum = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->value('DispensingCSlip');
                    $medSysReferenceNum = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->value('ChargeSlip');
                } else {
                    throw new \Exception("Failed to increment charge slips / transaction sequences");
                }
            } else {
                $assessnum_sequence = SystemSequence::where('code', 'GAN')->first();
            }

            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $patient_Name = $request->payload['patient_Name'];
            $case_No = $request->payload['case_No'];
            $account = $request->payload['account'];
            $requestDoctorID = $request->payload['attending_Doctor'];
            $requestDoctorName = $request->payload['attending_Doctor_fullname'];
            $patient_type = $request->payload['patient_type'];

            if (isset($request->payload['selectedItems']) && count($request->payload['selectedItems']) > 0) {
                foreach ($request->payload['selectedItems'] as $items) {
                    $revenueID = $items['code'];
                    $itemID = $items['map_item_id'];
                    $warehouseID = $items['warehouse_id'];
                    $item_name = $items['item_name'];
                    $quantity = $items['quantity'];
                    $item_OnHand = $items['item_OnHand'];
                    $amount = floatval(str_replace([',', '₱'], '', $items['amount']));
                    $requestNum = $revenueID . $medSysRequestNum;
                    $referenceNum = 'C' . $medSysReferenceNum . 'M';
                    $refNumSequence = $revenueID . $medSysReferenceNum;

                    if ($account == 'Cash Transaction') {
                        CashAssessment::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'transdate'                 => $today,
                            'assessnum'                 => $assessnum_sequence,
                            'drcr'                      => 'C',
                            'revenueID'                 => $revenueID,
                            'refNum'                    => $refNumSequence,
                            'itemID'                    => $itemID,
                            'quantity'                  => $quantity,
                            'requestDescription'        => $item_name,
                            'requestDoctorID'           => $requestDoctorID,
                            'requestDoctorName'         => $requestDoctorName,
                            'departmentID'              => $revenueID,
                            'issupplies'                => 1,
                            'userId'                    => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'hostname'                  => (new GetIP())->getHostname(),
                            'createdBy'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'created_at'                => $today,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            MedSysCashAssessment::create([
                                'HospNum'               => $patient_Id,
                                'IdNum'                 => $case_No . 'B',
                                'Name'                  => $patient_Name,
                                'TransDate'             => $today,
                                'AssessNum'             => $assessnum_sequence,
                                'DrCr'                  => 'C',
                                'ItemID'                => $itemID,
                                'Quantity'              => $quantity,
                                'RefNum'                => $refNumSequence,
                                'Amount'                => $amount,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'RevenueID'             => $revenueID,
                                'RequestDocID'          => $requestDoctorID,
                                'DepartmentID'          => $revenueID,
                            ]);
                        }
                    } else if ($account == 'Insurance Transaction') {
                        NurseLogBook::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'revenue_Id'                => $revenueID,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'item_Id'                   => $itemID,
                            'description'               => $item_name,
                            'Quantity'                  => $quantity,
                            'item_OnHand'               => $item_OnHand,
                            'amount'                    => $amount,
                            'section_Id'                => $warehouseID,
                            'issupplies'                => 1,
                            'record_Status'             => 'X',
                            'user_Id'                   => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'createdat'                 => $today,
                            'createdby'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            tbNurseLogBook::create([
                                'Hospnum'               => $patient_Id,
                                'IdNum'                 => $case_No . 'B',
                                'PatientType'          => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                                'RequestDate'           => $today,
                                'ItemID'                => $itemID,
                                'Description'           => $item_name,
                                'Quantity'              => $quantity,
                                'Amount'                => $amount,
                                'RecordStatus'          => 'X',
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'ProcessBy'             => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'ProcessDate'           => $today,
                                'RequestNum'            => $requestNum,
                                'ReferenceNum'          => $referenceNum,
                            ]);
                        }
                    }
                }
            }

            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            return response()->json(['message' => 'Requisition Saved Successfully'], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            return response()->json(["msg" => "Debug: " . $e->getMessage(), "line" => $e->getLine(), "file" => $e->getFile()], 500);
        }
    }
    public function saveMedicineRequisition(Request $request) 
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        try {
            $checkUser = null;
            if (isset($request->payload['user_userid']) && isset($request->payload['user_passcode'])) {
                $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
                if (!$checkUser) {
                    return response()->json([
                        'message' => 'Incorrect Username or Password',
                    ], 404);
                }
            }

            if ($this->check_is_allow_medsys) {
                $cashAssessmentSequence = new GlobalChargingSequences();
                $cashAssessmentSequence->incrementSequence(); 
                $assessnum_sequence = $cashAssessmentSequence->getSequence();
                $assessnum_sequence = $assessnum_sequence['MedSysCashSequence'];
                $inventoryChargeSlip = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->increment('DispensingCSlip');
                $nurseChargeSlip = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->increment('ChargeSlip');
                if ($inventoryChargeSlip && $nurseChargeSlip) {
                    $medSysRequestNum = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->value('DispensingCSlip');
                    $medSysReferenceNum = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->value('ChargeSlip');
                } else {
                    throw new \Exception("Failed to increment charge slips / transaction sequences");
                }
            } else {
                $assessnum_sequence = SystemSequence::where('code', 'GAN')->first();
            }

            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $patient_Name = $request->payload['patient_Name'];
            $case_No = $request->payload['case_No'];
            $account = $request->payload['account'];
            $requestDoctorID = $request->payload['attending_Doctor'];
            $requestDoctorName = $request->payload['attending_Doctor_fullname'];
            $patient_type = $request->payload['patient_type'];

            if (isset($request->payload['selectedItems']) && count($request->payload['selectedItems']) > 0) {
                foreach ($request->payload['selectedItems'] as $items) {
                    $revenueID = $items['code'];
                    $itemID = $items['map_item_id'];
                    $warehouseID = $items['warehouse_id'];
                    $item_name = $items['item_name'];
                    $quantity = $items['quantity'];
                    $item_OnHand = $items['item_OnHand'];
                    $dosage = $items['frequency'];
                    $stat = $items['stat'];
                    $amount = floatval(str_replace([',', '₱'], '', $items['amount']));
                    $requestNum = $revenueID . $medSysRequestNum;
                    $referenceNum = 'C' . $medSysReferenceNum . 'M';
                    $refNumSequence = $revenueID . $medSysReferenceNum;

                    if ($account == 'Cash Transaction') {
                        CashAssessment::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'transdate'                 => $today,
                            'assessnum'                 => $assessnum_sequence,
                            'drcr'                      => 'C',
                            'revenueID'                 => $revenueID,
                            'refNum'                    => $refNumSequence,
                            'itemID'                    => $itemID,
                            'quantity'                  => $quantity,
                            'requestDescription'        => $item_name,
                            'dosage'                    => $dosage,
                            'requestDoctorID'           => $requestDoctorID,
                            'requestDoctorName'         => $requestDoctorName,
                            'departmentID'              => $revenueID,
                            'ismedicine'                => 1,
                            'stat'                      => $stat,
                            'userId'                    => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'hostname'                  => (new GetIP())->getHostname(),
                            'createdBy'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'created_at'                => $today,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            MedSysCashAssessment::create([
                                'HospNum'               => $patient_Id,
                                'IdNum'                 => $case_No . 'B',
                                'Name'                  => $patient_Name,
                                'TransDate'             => $today,
                                'AssessNum'             => $assessnum_sequence,
                                'DrCr'                  => 'C',
                                'ItemID'                => $itemID,
                                'Quantity'              => $quantity,
                                'RefNum'                => $refNumSequence,
                                'Amount'                => $amount,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'RevenueID'             => $revenueID,
                                'RequestDocID'          => $requestDoctorID,
                                'DepartmentID'          => $revenueID,
                            ]);
                        }
                    } else if ($account == 'Insurance Transaction') {
                        NurseLogBook::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'revenue_Id'                => $revenueID,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'item_Id'                   => $itemID,
                            'description'               => $item_name,
                            'Quantity'                  => $quantity,
                            'dosage'                    => $dosage,
                            'amount'                    => $amount,
                            'ismedicine'                => 1,
                            'record_Status'             => 'X',
                            'section_Id'                => $warehouseID,
                            'stat'                      => $stat,
                            'user_Id'                   => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'createdat'                 => $today,
                            'createdby'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            tbNurseLogBook::create([
                                'Hospnum'               => $patient_Id,
                                'IdNum'                 => $case_No . 'B',
                                'PatientType'          => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                                'RequestDate'           => $today,
                                'ItemID'                => $itemID,
                                'Description'           => $item_name,
                                'Quantity'              => $quantity,
                                'Dosage'                => $dosage,
                                'Amount'                => $amount,
                                'RecordStatus'          => 'X',
                                'Stat'                  => $stat,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'ProcessBy'             => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'ProcessDate'           => $today,
                                'RequestNum'            => $requestNum,
                                'ReferenceNum'          => $referenceNum,
                            ]);
                        }
                    }
                }
            }

            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            return response()->json(['message' => 'Requisition Saved Successfully'], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            return response()->json(["msg" => "Debug: " . $e->getMessage(), "line" => $e->getLine(), "file" => $e->getFile()], 500);
        }
    }
    public function saveProcedureRequisition(Request $request) 
    {

    }
}
