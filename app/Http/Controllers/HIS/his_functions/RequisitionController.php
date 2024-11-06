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

        } catch(\Exception $e) {
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
                // No sequence fallback for inventory charge slip and nurse charge slip sa atung DB.
            }

            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $patient_Name = $request->payload['patient_Name'];
            $case_No = $request->payload['case_No'];
            $account = $request->payload['account'];
            $requestDoctorID = $request->payload['attending_Doctor'];
            $requestDoctorName = $request->payload['attending_Doctor_fullname'];

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
                            // 'refNum' SUBJECT TO ADD
                            'itemID'                    => $itemID,
                            'quantity'                  => $quantity,
                            'requestDoctorID'           => $requestDoctorID,
                            'requestDoctorName'         => $requestDoctorName,
                            'departmentID'              => $revenueID,
                            'userId'                    => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'hostname'                  => (new GetIP())->getHostname(),
                            'createdBy'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'created_at'                => $today,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            MedSysCashAssessment::create([
                                'HospNum'               => $patient_Id,
                                'IdNum'                 => $case_No,
                                'Name'                  => $patient_Name,
                                'TransDate'             => $today,
                                'AssessNum'             => $assessnum_sequence,
                                'DrCr'                  => 'C',
                                'ItemID'                => $itemID,
                                'Quantity'              => $quantity,
                                // 'RefNum'  SUBJECT TO ADD
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
                            'revenue_Id'                => $revenueID,
                            'requestNum'                => $requestNum,
                            'referenceNum'              => $referenceNum,
                            'item_Id'                   => $itemID,
                            'description'               => $item_name,
                            'Quantity'                  => $quantity,
                            'amount'                    => $amount,
                            'user_Id'                   => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            // 'remarks'
                            'createdat'                 => $today,
                            'createdby'                 => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            tbNurseLogBook::create([
                                'Hospnum'               => $patient_Id,
                                'IdNum'                 => $case_No,
                                ''
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
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function saveMedicineRequisition(Request $request) 
    {

    }
    public function saveProcedureRequisition(Request $request) 
    {

    }
}
