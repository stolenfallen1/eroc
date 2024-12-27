<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Itemmasters;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\ExamLaboratoryProfiles;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\LaboratoryMaster;
use App\Models\HIS\his_functions\NurseCommunicationFile;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\MedSysCashAssessment;
use App\Models\HIS\medsys\MedSysDailyOut;
use App\Models\HIS\medsys\tbLABMaster;
use App\Models\HIS\medsys\tbNurseCommunicationFile;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\MMIS\inventory\InventoryTransaction;
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
    protected $check_is_allow_laboratory_auto_rendering;
    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->check_is_allow_laboratory_auto_rendering = (new SysGlobalSetting())->check_is_allow_laboratory_auto_rendering();
    }
    public function getLabItems($item_id) 
    {
        try {
            $data = ExamLaboratoryProfiles::with('lab_exams')
                ->where('map_profile_id', $item_id)
                ->get();
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getBarCode($barcode_prefix, $sequence, $specimenId) 
    {
        $barcode = $barcode_prefix . $sequence . $specimenId;
        $barcodeLength = strlen($barcode);
        switch ($barcodeLength) {
            case 4: 
                $barcode = 'XXXXXXXX' . $barcode;
                break;
            case 5:
                $barcode = 'XXXXXXX' . $barcode;
                break;
            case 6:
                $barcode = 'XXXXXX' . $barcode;
            case 7:
                $barcode = 'XXXXX' . $barcode;
                break;
            case 8:
                $barcode = 'XXXX' . $barcode;
                break;
            case 9: 
                $barcode = 'XXX' . $barcode;
            case 10: 
                $barcode = 'XX' . $barcode;
                break;
            case 11:
                $barcode = 'X' . $barcode;
                break;
        }
        return $barcode;
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

            if ($request->roleID == 27) {
                $priceColumn = 'item_Selling_Price_In';
                $items = Itemmasters::with(['wareHouseItems' => function ($query) use ($request, $priceColumn) {
                    $query->where('warehouse_Id', $request->warehouseID)
                            ->select('id', 'item_Id', 'item_OnHand', 'item_ListCost', DB::raw("$priceColumn as price"));
                }])
                ->whereHas('wareHouseItems', function ($query) use ($request) {
                    $query->where('warehouse_Id', $request->warehouseID);
                })
                ->orderBy('item_name', 'asc');
            } else {                
                $priceColumn = $request->patienttype == 1 ? 'item_Selling_Price_Out' : 'item_Selling_Price_In';
                $items = Itemmasters::with(['wareHouseItems' => function ($query) use ($request, $priceColumn) {
                    $query->where('warehouse_Id', $request->warehouseID)
                            ->select('id', 'item_Id', 'item_OnHand', 'item_ListCost', DB::raw("$priceColumn as price"));
                }])
                ->whereHas('wareHouseItems', function ($query) use ($request) {
                    $query->where('warehouse_Id', $request->warehouseID);
                })
                ->orderBy('item_name', 'asc');
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
    public function getPatientRequisitions(Request $request) 
    {
        try {
            $patient_Id = $request->items['patient_Id'];
            $case_No = $request->items['case_No'];
            $account = $request->items['account'];
            $keyword = $request->keyword ?? ''; 
            $data = []; 

            if ($account == 1) {
                $cashAssessments = CashAssessment::where('patient_Id', $patient_Id)
                    ->where('case_No', $case_No)
                    ->where('recordStatus', '27')
                    ->whereNull('ORNumber')
                    ->whereNotNull('refNum')
                    ->where(function($query) use ($keyword) {
                        $query->where('issupplies', 1)
                            ->orWhere('ismedicine', 1)
                            ->orWhere('isprocedure', 1);
                    })
                    ->when($keyword, function($query) use ($keyword) {
                        $query->where(function($query) use ($keyword) {
                            $query->where('requestDescription', 'LIKE', "%{$keyword}%")
                                ->orWhere('itemID', 'LIKE', "%{$keyword}%")
                                ->orWhere('revenueID', 'LIKE', "%{$keyword}%");
                        });
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();

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
                    ->where('record_Status', 'X') 
                    ->where(function($query) {
                        $query->where('issupplies', 1)
                            ->orWhere('ismedicine', 1)
                            ->orWhere('isprocedure', 1);
                    })
                    ->when($keyword, function($query) use ($keyword) {
                        $query->where(function($query) use ($keyword) {
                            $query->where('description', 'LIKE', "%{$keyword}%")
                                ->orWhere('item_Id', 'LIKE', "%{$keyword}%")
                                ->orWhere('revenue_Id', 'LIKE', "%{$keyword}%");
                        });
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
                    ->when($keyword, function($query) use ($keyword) {
                        $query->where(function($query) use ($keyword) {
                            $query->where('description', 'LIKE', "%{$keyword}%")
                                ->orWhere('item_Id', 'LIKE', "%{$keyword}%")
                                ->orWhere('revenue_Id', 'LIKE', "%{$keyword}%");
                        });
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
            $patient_Name = $request->payload['patient_Name'];
            $case_No = $request->payload['case_No'];
            $account = $request->payload['account'];
            $requestDoctorID = $request->payload['attending_Doctor'];
            $requestDoctorName = $request->payload['attending_Doctor_fullname'];
            $patient_type = $request->payload['patient_Type'];
            $guarantor_Id = $request->payload['guarantor_Id'];

            if (isset($request->payload['selectedItems']) && count($request->payload['selectedItems']) > 0) {
                foreach ($request->payload['selectedItems'] as $items) {
                    $revenueID = $items['code'];
                    $itemID = $items['map_item_id'];
                    $warehouseID = $items['warehouse_id'];
                    $item_name = $items['item_name'];
                    $quantity = $items['quantity'];
                    $item_OnHand = $items['item_OnHand'];
                    $item_ListCost = $items['item_ListCost'];
                    $price = $items['price'];
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
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'transdate'                 => $today,
                            'assessnum'                 => $assessnum_sequence,
                            'drcr'                      => 'C',
                            'revenueID'                 => $revenueID,
                            'refNum'                    => $refNumSequence,
                            'itemID'                    => $itemID,
                            'item_ListCost'             => $item_ListCost,
                            'item_Selling_Amount'       => $price,
                            'item_OnHand'               => $item_OnHand,
                            'quantity'                  => $quantity,
                            'section_Id'                => $warehouseID,
                            'amount'                    => $amount,
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
                        HISBillingOut::create([
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'transDate'                 => $today,
                            'msc_price_scheme_id'       => 2,
                            'revenueID'                 => $revenueID,
                            'itemID'                    => $itemID,
                            'quantity'                  => $quantity,
                            'refNum'                    => $requestNum,
                            'ChargeSlip'                => $requestNum,
                            'amount'                    => $amount,
                            'userId'                    => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'request_doctors_id'        => $requestDoctorID,
                            'net_amount'                => $amount,
                            'hostName'                  => (new GetIP())->getHostname(),
                            'accountnum'                => $guarantor_Id,
                            'auto_discount'             => 0,
                            'createdby'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'created_at'                => $today,
                        ]);
                        NurseLogBook::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'revenue_Id'                => $revenueID,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'item_Id'                   => $itemID,
                            'description'               => $item_name,
                            'Quantity'                  => $quantity,
                            'item_OnHand'               => $item_OnHand,
                            'item_ListCost'             => $item_ListCost,
                            'price'                     => $price,
                            'amount'                    => $amount,
                            'section_Id'                => $warehouseID,
                            'issupplies'                => 1,
                            'record_Status'             => 'X',
                            'user_Id'                   => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'createdat'                 => $today,
                            'createdby'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        NurseCommunicationFile::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'item_Id'                   => $itemID,
                            'amount'                    => $amount,
                            'quantity'                  => $quantity,
                            'section_Id'                => $warehouseID,
                            'request_Date'              => $today,
                            'revenue_Id'                => $revenueID,
                            'record_Status'             => 'X',
                            'user_Id'                   => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            MedSysDailyOut::create([
                                'Hospnum'               => $patient_Id,
                                'IDNum'                 => $case_No . 'B',
                                'TransDate'             => $today,
                                'RevenueID'             => $revenueID,
                                'ItemID'                => $itemID,
                                'Quantity'              => $quantity,
                                'RefNum'                => $requestNum,
                                'ChargeSlip'            => $requestNum,
                                'Amount'                => $amount,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'HostName'              => (new GetIP())->getHostname(),
                                'AutoDiscount'          => 0,
                            ]);
                            tbNurseLogBook::create([
                                'Hospnum'               => $patient_Id,
                                'IdNum'                 => $case_No . 'B',
                                'PatientType'           => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'O' : 'I'), // O rasad daw ang sa emergency
                                'RevenueID'             => $revenueID,
                                'RequestDate'           => $today,
                                'ItemID'                => $itemID,
                                'Description'           => $item_name,
                                'Quantity'              => $quantity,
                                'Amount'                => $amount,
                                'RecordStatus'          => null,
                                'SectionID'             => $warehouseID,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'RequestNum'            => $requestNum,
                                'ReferenceNum'          => $referenceNum,
                            ]);
                            tbNurseCommunicationFile::create([
                                'HospNum'               => $patient_Id,
                                'IDnum'                 => $case_No . 'B',
                                'PatientType'           => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'O' : 'I'), // O rasad daw ang sa emergency
                                'ItemID'                => $itemID,
                                'Amount'                => $amount,
                                'Quantity'              => $quantity,
                                'SectionID'             => $warehouseID,
                                'RequestDate'           => $today,
                                'RevenueID'             => $revenueID,
                                'RecordStatus'          => null,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
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
            $patient_Name = $request->payload['patient_Name'];
            $case_No = $request->payload['case_No'];
            $account = $request->payload['account'];
            $requestDoctorID = $request->payload['attending_Doctor'];
            $requestDoctorName = $request->payload['attending_Doctor_fullname'];
            $patient_type = $request->payload['patient_Type'];
            $guarantor_Id = $request->payload['guarantor_Id'];

            if (isset($request->payload['selectedItems']) && count($request->payload['selectedItems']) > 0) {
                foreach ($request->payload['selectedItems'] as $items) {
                    $revenueID = $items['code'];
                    $itemID = $items['map_item_id'];
                    $warehouseID = $items['warehouse_id'];
                    $item_name = $items['item_name'];
                    $quantity = $items['quantity'];
                    $price = $items['price'];
                    $item_OnHand = $items['item_OnHand'];
                    $item_ListCost = $items['item_ListCost'];
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
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'transdate'                 => $today,
                            'assessnum'                 => $assessnum_sequence,
                            'drcr'                      => 'C',
                            'revenueID'                 => $revenueID,
                            'refNum'                    => $refNumSequence,
                            'itemID'                    => $itemID,
                            'item_ListCost'             => $item_ListCost,
                            'item_Selling_Amount'       => $price,
                            'item_OnHand'               => $item_OnHand,
                            'quantity'                  => $quantity,
                            'section_Id'                => $warehouseID,
                            'amount'                    => $amount,
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
                        HISBillingOut::create([
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'transDate'                 => $today,
                            'msc_price_scheme_id'       => 2,
                            'revenueID'                 => $revenueID,
                            'itemID'                    => $itemID,
                            'quantity'                  => $quantity,
                            'refNum'                    => $requestNum,
                            'ChargeSlip'                => $requestNum,
                            'amount'                    => $amount,
                            'userId'                    => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'request_doctors_id'        => $requestDoctorID,
                            'net_amount'                => $amount,
                            'hostName'                  => (new GetIP())->getHostname(),
                            'accountnum'                => $guarantor_Id,
                            'auto_discount'             => 0,
                            'createdby'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'created_at'                => $today,
                        ]);
                        NurseLogBook::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'revenue_Id'                => $revenueID,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'item_Id'                   => $itemID,
                            'description'               => $item_name,
                            'Quantity'                  => $quantity,
                            'item_OnHand'               => $item_OnHand,
                            'item_ListCost'             => $item_ListCost,
                            'dosage'                    => $dosage,
                            'price'                     => $price,
                            'amount'                    => $amount,
                            'ismedicine'                => 1,
                            'record_Status'             => 'X',
                            'section_Id'                => $warehouseID,
                            'stat'                      => $stat,
                            'user_Id'                   => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'createdat'                 => $today,
                            'createdby'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        NurseCommunicationFile::create([
                            'branch_id'                 => 1,
                            'patient_Id'                => $patient_Id,
                            'case_No'                   => $case_No,
                            'patient_Name'              => $patient_Name,
                            'patient_Type'              => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'item_Id'                   => $itemID,
                            'amount'                    => $amount,
                            'quantity'                  => $quantity,
                            'dosage'                    => $dosage,
                            'section_Id'                => $warehouseID,
                            'request_Date'              => $today,
                            'revenue_Id'                => $revenueID,
                            'record_Status'             => 'X',
                            'user_Id'                   => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'stat'                      => $stat,
                            'createdat'                 => $today,
                            'createdby'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            MedSysDailyOut::create([
                                'Hospnum'               => $patient_Id,
                                'IDNum'                 => $case_No . 'B',
                                'TransDate'             => $today,
                                'RevenueID'             => $revenueID,
                                'ItemID'                => $itemID,
                                'Quantity'              => $quantity,
                                'RefNum'                => $requestNum,
                                'ChargeSlip'            => $requestNum,
                                'Amount'                => $amount,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'HostName'              => (new GetIP())->getHostname(),
                                'AutoDiscount'          => 0,
                            ]);
                            tbNurseLogBook::create([
                                'Hospnum'               => $patient_Id,
                                'IdNum'                 => $case_No . 'B',
                                'PatientType'           => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'O' : 'I'), // O rasad daw ang sa emergency
                                'RevenueID'             => $revenueID,
                                'RequestDate'           => $today,
                                'ItemID'                => $itemID,
                                'Description'           => $item_name,
                                'Quantity'              => $quantity,
                                'Dosage'                => $dosage,
                                'Amount'                => $amount,
                                'RecordStatus'          => null,
                                'Stat'                  => $stat,
                                'SectionID'             => $warehouseID,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'RequestNum'            => $requestNum,
                                'ReferenceNum'          => $referenceNum,
                            ]);
                            tbNurseCommunicationFile::create([
                                'HospNum'               => $patient_Id,
                                'IDnum'                 => $case_No . 'B',
                                'PatientType'           => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'O' : 'I'), // O rasad daw ang sa emergency
                                'ItemID'                => $itemID,
                                'Amount'                => $amount,
                                'Quantity'              => $quantity,
                                'Dosage'                => $dosage,
                                'SectionID'             => $warehouseID,
                                'RequestDate'           => $today,
                                'RevenueID'             => $revenueID,
                                'RecordStatus'          => null,
                                'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'RequestNum'            => $requestNum,
                                'ReferenceNum'          => $referenceNum,
                                'Stat'                  => $stat,
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
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_laboratory')->beginTransaction();
        DB::connection('sqlsrv_medsys_laboratory')->beginTransaction();
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

            $cashAssessmentSequences = new GlobalChargingSequences();
            $cashAssessmentSequences->incrementSequence(); 

            if ($this->check_is_allow_medsys) {
                $assessnum_sequence = $cashAssessmentSequences->getSequence();
            } else {
                $chargeslip_sequence = SystemSequence::where('code', 'GCN')->first();
                $assessnum_sequence = SystemSequence::where('code', 'GAN')->first();

                if ($chargeslip_sequence && $assessnum_sequence) {
                    $chargeslip_sequence->increment('seq_no');
                    $chargeslip_sequence->increment('recent_generated');
                    $assessnum_sequence->increment('seq_no');
                    $assessnum_sequence->increment('recent_generated');
                } else {
                    throw new \Exception('Sequences not found');
                }
            }

            $revenueCodeSequences = [
                'LB'    => 'MedSysLabSequence',
                'XR'    => 'MedSysXrayUltraSoundSequence',
                'US'    => 'MedSysXrayUltraSoundSequence',
                'CT'    => 'MedSysCTScanSequence',
                'MI'    => 'MedSysMRISequence',
                'MM'    => 'MedSysMammoSequence',
                'WC'    => 'MedSysCentreForWomenSequence',
                'NU'    => 'MedSysNuclearMedSequence',
                'ER'    => 'MedSysCashSequence'
            ];

            $sequenceGenerated = [];
            $xr_us_codes = ['XR', 'US'];
            $xr_us_incremented = false;

            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $patient_Name = $request->payload['patient_Name'];
            $account = $request->payload['account'];
            $doctor_id = $request->payload['attending_Doctor'];
            $doctor_name = $request->payload['attending_Doctor_fullname'];
            $patient_type = $request->payload['patient_Type'];
            $guarantor_Id = $request->payload['guarantor_Id'];

            if (isset($request->payload['selectedItems']) && count($request->payload['selectedItems']) > 0) {
                foreach ($request->payload['selectedItems'] as $procedure) {
                    $revenue_Id = $procedure['code'];
                    $item_Id = $procedure['map_item_id'];
                    $item_name = $procedure['item_name'];
                    $quantity = $procedure['quantity'];
                    $stat = $procedure['stat'];
                    $specimen = $procedure['specimen'] ?? null;
                    $amount = floatval(str_replace([',', '₱'], '', $procedure['amount']));
                    $barcode_prefix = $procedure['barcode_prefix'] ?? null;
                    $drcr = $procedure['drcr'];
                    $lgrp = $procedure['lgrp'];
                    $form = $procedure['form'] ?? null;

                    if (in_array($revenue_Id, $xr_us_codes)) {
                        if (!$xr_us_incremented) {
                            $cashAssessmentSequences->incrementSequence('XR'); // Increment XR and US sequence once only for both
                            $chargeslip_sequence = $cashAssessmentSequences->getSequence();
                            $xr_us_incremented = true;
                        }
                    } else {
                        if (!isset($sequenceGenerated[$revenue_Id])) {
                            $cashAssessmentSequences->incrementSequence($revenue_Id);
                            $chargeslip_sequence = $cashAssessmentSequences->getSequence();
                            $sequenceGenerated[$revenue_Id] = true;
                        }
                    }

                    if (array_key_exists($revenue_Id, $revenueCodeSequences)) {
                        $sequenceType = $revenueCodeSequences[$revenue_Id];
                        $sequence = $revenue_Id . ($this->check_is_allow_medsys && isset($chargeslip_sequence[$sequenceType]) 
                            ? $chargeslip_sequence[$sequenceType] 
                            : $chargeslip_sequence['seq_no']);
                    }

                    if ($barcode_prefix === null) {
                        $barcode = '';
                    } else {
                        $barcode = $this->getBarCode($barcode_prefix, $sequence, $specimen);
                    }

                    if ($account == 'Cash Transaction') {
                        CashAssessment::create([
                            'branch_id'                     => 1,
                            'patient_Id'                    => $patient_Id,
                            'case_No'                       => $case_No,
                            'patient_Name'                  => $patient_Name,
                            'patient_Type'                  => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'transdate'                     => $today,
                            'assessnum'                     => $assessnum_sequence['MedSysCashSequence'],
                            'drcr'                          => 'C',
                            'form'                          => $form,
                            'stat'                          => $stat,
                            'revenueID'                     => $revenue_Id,
                            'itemID'                        => $item_Id,
                            'requestDescription'            => $item_name,
                            'quantity'                      => $quantity,
                            'refNum'                        => $sequence,
                            'amount'                        => $amount,
                            'specimenId'                    => $specimen,
                            'requestDoctorID'               => $doctor_id,
                            'requestDoctorName'             => $doctor_name,
                            'departmentID'                  => $revenue_Id,
                            'Barcode'                       => $barcode,
                            'isprocedure'                   => 1,
                            'userId'                        => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'hostname'                      => (new GetIP())->getHostname(),
                            'createdBy'                     => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'created_at'                    => $today,
                        ]);
                        if ($this->check_is_allow_medsys):
                            MedSysCashAssessment::create([
                                'HospNum'                   => $patient_Id,
                                'IdNum'                     => $case_No . 'B',
                                'Name'                      => $patient_Name,
                                'TransDate'                 => $today,
                                'AssessNum'                 => $assessnum_sequence['MedSysCashSequence'],
                                'DrCr'                      => 'C',
                                'ItemID'                    => $item_Id,
                                'Quantity'                  => $quantity,
                                'RefNum'                    => $sequence,
                                'ChargeSlip'                => $sequence,
                                'Amount'                    => $amount,
                                'SpecimenId'                => $specimen,
                                'Barcode'                   => $barcode,
                                'STAT'                      => $stat,
                                'DoctorName'                => $doctor_name,
                                'UserID'                    => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'RevenueID'                 => $revenue_Id,
                                'DepartmentID'              => $revenue_Id,
                            ]);
                        endif;
                    } else if ($account == 'Insurance Transaction') {
                        NurseLogBook::create([
                            'branch_id'                     => 1,
                            'patient_Id'                    => $patient_Id,
                            'case_No'                       => $case_No,
                            'patient_Name'                  => $patient_Name,
                            'patient_Type'                  => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'revenue_Id'                    => $revenue_Id,
                            'requestNum'                    => $sequence,
                            'item_Id'                       => $item_Id,
                            'description'                   => $item_name,
                            'Quantity'                      => $quantity,
                            'amount'                        => $amount,
                            'isprocedure'                   => 1,
                            'record_Status'                 => 'X',
                            'stat'                          => $stat,
                            'user_Id'                       => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'createdat'                     => $today,
                            'createdby'                     => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        NurseCommunicationFile::create([
                            'branch_id'                     => 1,
                            'patient_Id'                    => $patient_Id,
                            'case_No'                       => $case_No,
                            'patient_Name'                  => $patient_Name,
                            'patient_Type'                  => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'item_Id'                       => $item_Id,
                            'amount'                        => $amount,
                            'quantity'                      => $quantity,
                            'request_Date'                  => $today,
                            'revenue_Id'                    => $revenue_Id,
                            'record_Status'                 => 'X',
                            'user_Id'                       => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'requestNum'                    => $sequence,
                            'stat'                          => $stat,
                            'createdat'                     => $today,
                            'createdby'                     => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        HISBillingOut::create([
                            'patient_Id'                    => $patient_Id,
                            'case_No'                       => $case_No,
                            'patient_Type'                  => $patient_type == 'Out-Patient' ? 'O' : ($patient_type == 'Emergency' ? 'E' : 'I'),
                            'accountnum'                    => $guarantor_Id,  
                            'transDate'                     => $today,
                            'msc_price_scheme_id'           => 2,
                            'revenueID'                     => $revenue_Id,
                            'drcr'                          => $drcr,
                            'lgrp'                          => $lgrp,
                            'itemID'                        => $item_Id,
                            'quantity'                      => $quantity,
                            'refNum'                        => $sequence,
                            'ChargeSlip'                    => $sequence,
                            'amount'                        => $amount,
                            'Barcode'                       => $barcode,
                            'userId'                        => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'request_doctors_id'            => $doctor_id,
                            'net_amount'                    => $amount,
                            'hostName'                      => (new GetIP())->getHostname(),
                            'auto_discount'                 => 0,
                            'created_at'                    => $today,
                            'createdby'                     => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys):
                            MedSysDailyOut::create([
                                'HospNum'                   => $patient_Id,
                                'IDNum'                     => $case_No . 'B',
                                'TransDate'                 => $today,
                                'RevenueID'                 => $revenue_Id,
                                'ItemID'                    => $item_Id,
                                'DrCr'                      => $drcr,
                                'Quantity'                  => $quantity,
                                'RefNum'                    => $sequence,
                                'ChargeSlip'                => $sequence,
                                'Amount'                    => $amount,
                                'UserID'                    => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'HostName'                  => (new GetIP())->getHostname(),
                                'AutoDiscount'              => 0,
                            ]);
                        endif;
                        // Throw the data to the respective table for the procedure para ma carry order / render 
                        switch ($revenue_Id) {
                            case 'LB':
                                $recordStatus = $this->check_is_allow_laboratory_auto_rendering ? 'W' : 'X';
                                $processedBy = $this->check_is_allow_laboratory_auto_rendering ? ($checkUser ? $checkUser->idnumber : Auth()->user()->idnumber) : null;
                                $processedDate = $this->check_is_allow_laboratory_auto_rendering ? $today : null;
                                // Way labot sa bungkag ang CBC, Routine Urinalysis and Stool Exam Routine
                                if (($item_Id != 160 && $item_Id != 149 && $item_Id != 145) && ($form == 'C' || $form == 'P')) {
                                    $labProfileData = $this->getLabItems($item_Id);
                                    if ($labProfileData->getStatusCode() === 200) {
                                        $labItems = $labProfileData->getData()->data;
                                        foreach ($labItems as $labItem) {
                                            foreach ($labItem->lab_exams as $exam) {
                                                LaboratoryMaster::create([
                                                    'patient_Id'            => $patient_Id,
                                                    'case_No'               => $case_No,
                                                    'transdate'             => $today,
                                                    'refNum'                => $sequence,
                                                    'profileId'             => $exam->map_profile_id,
                                                    'item_Charged'          => $exam->map_profile_id,
                                                    'itemId'                => $exam->map_exam_id,
                                                    'quantity'              => $quantity,
                                                    'amount'                => 0, // As per sir joe instructions, wala pay price per exam sa panel / package
                                                    'NetAmount'             => 0, // As per sir joe instructions, wala pay price per exam sa panel / package
                                                    'doctor_Id'             => $doctor_id,
                                                    'specimen_Id'           => $exam->map_specimen_id,
                                                    'processed_By'          => $processedBy,
                                                    'processed_Date'        => $processedDate,
                                                    'isrush'                => $stat == 1 ? 'N' : 'Y',
                                                    'request_Status'        => $recordStatus,
                                                    'result_Status'         => $recordStatus,
                                                    'userId'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                                    'barcode'               => $barcode,
                                                    'created_at'            => $today,
                                                    'createdby'             => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                                ]);
                                                if ($this->check_is_allow_medsys):
                                                    tbLABMaster::create([
                                                        'HospNum'           => $patient_Id,
                                                        'IdNum'             => $case_No . 'B',
                                                        'RefNum'            => $sequence,
                                                        'RequestStatus'     => $recordStatus,
                                                        'ItemId'            => $exam->map_exam_id,
                                                        'Amount'            => 0,
                                                        'Transdate'         => $today,
                                                        'DoctorId'          => $doctor_id,
                                                        'SpecimenId'        => $exam->map_specimen_id,
                                                        'UserId'            => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                                        'Quantity'          => $quantity,
                                                        'ResultStatus'      => $recordStatus,
                                                        'Barcode'           => $barcode,
                                                        'RUSH'              => $stat == 1 ? 'N' : 'Y',
                                                        'ProfileId'         => $exam->map_profile_id,
                                                        'ItemCharged'       => $exam->map_profile_id,
                                                    ]);
                                                endif;
                                            }
                                        }
                                    }
                                } else {
                                    LaboratoryMaster::create([
                                        'patient_Id'            => $patient_Id,
                                        'case_No'               => $case_No,
                                        'transdate'             => $today,
                                        'refNum'                => $sequence,
                                        'profileId'             => $item_Id,
                                        'item_Charged'          => $item_Id,
                                        'itemId'                => $item_Id,
                                        'quantity'              => $quantity,
                                        'amount'                => 0, // Kani kay nag libug ko should I use the amount sa procedure? or in-ani lang usa?
                                        'NetAmount'             => 0, // Kani kay nag libug ko should I use the amount sa procedure? or in-ani lang usa?
                                        'doctor_Id'             => $doctor_id,
                                        'specimen_Id'           => $specimen,
                                        'processed_By'          => $processedBy,
                                        'processed_Date'        => $processedDate,
                                        'isrush'                => $stat == 1 ? 'N' : 'Y',
                                        'request_Status'        => $recordStatus,
                                        'result_Status'         => $recordStatus,
                                        'userId'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                        'barcode'               => $barcode,
                                        'created_at'            => $today,
                                        'createdby'             => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                    ]);
                                    if ($this->check_is_allow_medsys):
                                        tbLABMaster::create([
                                            'HospNum'           => $patient_Id,
                                            'IdNum'             => $case_No . 'B',
                                            'RefNum'            => $sequence,
                                            'RequestStatus'     => $recordStatus,
                                            'ItemId'            => $item_Id,
                                            'Amount'            => 0,
                                            'Transdate'         => $today,
                                            'DoctorId'          => $doctor_id,
                                            'SpecimenId'        => $specimen,
                                            'UserId'            => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                            'Quantity'          => $quantity,
                                            'ResultStatus'      => $recordStatus,
                                            'RUSH'              => $stat == 1 ? 'N' : 'Y',
                                            'ProfileId'         => $item_Id,
                                            'ItemCharged'       => $item_Id,
                                        ]);
                                    endif;
                                }
                                break;
                            // ADD MORE CASES HERE
                            default: 
                                echo "TABLE FOR GENERAL PROCEDURE's asa i labay?";
                                break;
                        }
                    }
                }
                DB::connection('sqlsrv_billingOut')->commit();
                DB::connection('sqlsrv_medsys_billing')->commit();
                DB::connection('sqlsrv_laboratory')->commit();
                DB::connection('sqlsrv_medsys_laboratory')->commit();

                return response()->json(['message' => 'Requisition Saved Successfully'], 200);
            }

        } catch(\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_laboratory')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function getRenderedPatientRequisitions(Request $request) 
    {
        try {
            $data = NurseLogBook::where('patient_Id', $request->patient_Id)
                ->where('case_No', $request->case_No)
                ->where('record_Status', 'W')
                ->where(function ($query) {
                    $query->where('isprocedure', 1)
                            ->orWhere('ismedicine', 1)
                            ->orWhere('issupplies', 1);
                })
                ->orderBy('createdat', 'desc')
                ->get();
    
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function getCancelledRequisitions(Request $request) 
    {
        try {
            $data = NurseLogBook::where('patient_Id', $request->patient_Id)
                ->where('case_No', $request->case_No)
                ->where('record_Status', 'R')
                ->orderBy('createdat', 'desc')
                ->get();
    
            return response()->json($data, 200);
        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function onRevokeRequisition(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
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

            $today = Carbon::now();
            $remarks = $request->payload['remarks'];
            $account = $request->payload['account'];
            
            if (isset($request->payload['Items']) && count($request->payload['Items']) > 0) {
                foreach ($request->payload['Items'] as $items) {
                    $patient_Id = $items['patient_Id'];
                    $case_No = $items['case_No'];
                    if (isset($items['details'])) {
                        $revenueID = $items['details']['revenueID'] ?? $items['details']['revenue_Id'];
                        $itemID = $items['details']['itemID'] ?? $items['details']['item_Id'];
                        $refNum = $items['details']['refNum'] ?? $items['details']['requestNum'];
                        $isprocedure = $items['details']['isprocedure'] ?? null;

                        if ($account == 1) {
                            // Cash Transaction
                            CashAssessment::where('patient_Id', $patient_Id)
                            ->where('case_No', $case_No)
                            ->where('revenueID', $revenueID)
                            ->where('itemID', $itemID)
                            ->where('refNum', $refNum)
                            ->update([
                                'refNum'        => null,
                                'Remarks'       => $remarks,
                                'updatedBy'     => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'updated_at'    => $today,
                            ]);
                            if ($this->check_is_allow_medsys) {
                                MedSysCashAssessment::where('HospNum', $patient_Id)
                                    ->where('IdNum', $case_No . 'B')
                                    ->where('RevenueID', $revenueID)
                                    ->where('ItemID', $itemID)
                                    ->where('RefNum', $refNum)
                                    ->update([
                                        'RefNum'        => null,
                                        'RevokedBy'     => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                        'DateRevoked'   => $today,
                                    ]);
                            }

                        } else if ($account == 2) {
                            // Insurance Transaction
                            NurseLogBook::where('patient_Id', $patient_Id)
                                ->where('case_No', $case_No)
                                ->where('requestNum', $refNum)
                                ->where('item_Id', $itemID)
                                ->update([
                                    'remarks'           => $remarks,
                                    'record_Status'     => 'R',
                                    'cancelBy'          => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                    'cancelDate'        => $today,
                                    'updatedat'         => $today,
                                    'updatedby'         => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                ]);
                            NurseCommunicationFile::where('patient_Id', $patient_Id)
                                ->where('case_No', $case_No)
                                ->where('requestNum', $refNum)
                                ->where('item_Id', $itemID)
                                ->update([
                                    'remarks'           => $remarks,
                                    'record_Status'     => 'R',
                                    'cancelBy'          => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                    'cancelDate'        => $today,
                                    'updatedat'         => $today,
                                    'updatedby'         => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                ]);
                            // Revoke the data in the respective table for the procedures 
                            if ($isprocedure == 1) {
                                switch ($revenueID) {
                                    case 'LB':
                                        LaboratoryMaster::where('patient_Id', $patient_Id)
                                            ->where('case_No', $case_No)
                                            ->where('refNum', $refNum)
                                            ->where('profileId', $itemID)
                                            ->where('request_Status', 'X')
                                            ->where('result_Status', 'X')
                                            ->update([
                                                'request_Status'    => 'R',
                                                'result_Status'     => 'R',
                                                'remarks'           => $remarks,
                                                'canceled_By'       => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                                'canceled_Date'     => $today,
                                                'updated_at'        => $today,
                                                'updatedby'         => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                            ]);
                                        if ($this->check_is_allow_medsys):
                                            tbLABMaster::where('HospNum', $patient_Id)
                                                ->where('IdNum', $case_No . 'B')
                                                ->where('RefNum', $refNum)
                                                ->where('ProfileId', $itemID)
                                                ->where('RequestStatus', 'X')
                                                ->where('ResultStatus', 'X')
                                                ->update([
                                                    'RequestStatus'     => 'R',
                                                    'ResultStatus'      => 'R',
                                                    'Remarks'           => $remarks,
                                                ]);
                                        endif;
                                        break;
                                    // ADD MORE CASES HERE
                                    default:
                                    // Kani para sa mga general procedures?? 
                                        break;
                                }
                            }

                            if ($this->check_is_allow_medsys):
                                tbNurseLogBook::where('Hospnum', $patient_Id)
                                    ->where('IDnum', $case_No . 'B')
                                    ->where('RequestNum', $refNum)
                                    ->where('ItemID', $itemID)
                                    ->update([
                                        'Remarks'           => $remarks,
                                        'RecordStatus'      => 'R',
                                    ]);
                                tbNurseCommunicationFile::where('HospNum', $patient_Id)
                                    ->where('IDnum', $case_No . 'B')
                                    ->where('RequestNum', $refNum)
                                    ->where('ItemID', $itemID)
                                    ->update([
                                        'Remarks'           => $remarks,
                                        'RecordStatus'      => 'R',
                                    ]);
                            endif;
                        }
                    }
                }
                DB::connection('sqlsrv_billingOut')->commit();
                DB::connection('sqlsrv_medsys_billing')->commit();
                DB::connection('sqlsrv_patient_data')->commit();
                DB::connection('sqlsrv_medsys_nurse_station')->commit();
                return response()->json(['message' => 'Revoked Successfully'], 200); 
            }


        } catch(\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
