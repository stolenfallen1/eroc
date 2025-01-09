<?php

namespace App\Http\Controllers\HIS\charge_medicine_and_supply;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use DB;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Itemmasters;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\HIS\MedsysCashAssessment;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\medsys\tbInvStockCard;
use App\Models\User;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\medsys\MedSysDailyOut;
use App\Helpers\HIS\MedicineSuppliesCharges\DatabaseTransactionController;
use App\Helpers\HIS\MedicineSuppliesCharges\ChargeSuportProcess;
use App\Helpers\HIS\MedicineSuppliesCharges\CancelChargeItemsSupportProcess;

class EmergencyRoom extends Controller
{
    //
    protected $check_is_allow_medsys;
    protected $dbTransactionController;
    protected $chargeSuportProcess;
    protected $cancelSupportProcess;
    public function __construct() {
        $this->isproduction = true;
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->dbTransactionController = new DatabaseTransactionController();
        $this->chargeSuportProcess = new ChargeSuportProcess();
        $this->cancelSupportProcess = new CancelChargeItemsSupportProcess();
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
        $this->dbTransactionController->handleDatabaseTransactionProcess('start');
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
                $this->chargeSuportProcess->processItems($request, $checkUser, 'Medicines');
            }
            if(isset($request->payload['itemListCost'])) {
                $this->chargeSuportProcess->processItems($request, $checkUser, 'itemListCost');
            }
            if (isset($request->payload['Supplies']) && 
                count(array_filter($request->payload['Supplies'], function($item) {
                    return !empty($item['code']);
                })) > 0) {
                $this->chargeSuportProcess->processItems($request, $checkUser, 'Supplies');
            }
            $this->dbTransactionController->handleDatabaseTransactionProcess('commit');
            return response()->json(['message' => 'Charge processing successful'], 200);
        } catch (Exception $e) {
            $this->dbTransactionController->handleDatabaseTransactionProcess('rollback');
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }
    
    public function getMedicineSupplyCharges($id) {
        try {
            $accountType = DB::connection('sqlsrv_patient_data')
                        ->table('CDG_PATIENT_DATA.dbo.PatientRegistry')
                        ->select('guarantor_Name')
                        ->where('case_No', $id)
                        ->first();
            if($accountType->guarantor_Name === 'Self Pay') {
                $dataCharges = $this->dbTransactionController->queryItems('cash_assessment', $id);
            } else {
                $dataCharges = $this->dbTransactionController->queryItems('hmo', $id);
            }
            if ($dataCharges->isEmpty()) {
                return response()->json([
                    'message' => 'No Charges'
                ], 404);
            } 
            $charges = $this->dbTransactionController->handleItemMapping($dataCharges);
            return response()->json($charges, 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function revokedCharges(Request $request) {
        $this->dbTransactionController->handleDatabaseTransactionProcess('start');
        try {
            $checkUser = User::where([
                ['idnumber', '=', $request->payload['user_userid']],
                ['passcode', '=', $request->payload['user_passcode']]
            ])->first();
            if (!$checkUser) {
                return response()->json(['message' => 'Incorrect Username or Password'], 404);
            }
            if($request->payload['account'] !=='Self-Pay') {
                foreach($request->payload['reference_id'] as $reference_id) {
                    $cdg_mmis_inventory  = InventoryTransaction::where('trasanction_Reference_Number', $reference_id)->get();
                    $medsys_inventory    = tbInvStockCard::where('RefNum', $reference_id)->get();
                    $billingOut          = HISBillingOut::where('refNum', $reference_id)->get();
                    $medsys_dailyOut     = MedSysDailyOut::where('RefNum', $reference_id)->get();
                    if(!$billingOut->isEmpty() && !$medsys_inventory->isEmpty()) {
                        $this->cancelSupportProcess->processRevokedBillingout($request, $billingOut, $medsys_dailyOut, $checkUser);
                    }
                    $this->cancelSupportProcess->processRevokedHMOCharges($request, $checkUser, $cdg_mmis_inventory, $medsys_inventory);
                }
            } else {
                foreach($request->payload['reference_id'] as $reference_id) {
                    $getCashAssessment        = CashAssessment::where('RefNum', $reference_id)->get();
                    $getMedsysCashAssessment  = MedsysCashAssessment::where('RefNum', $reference_id)->get();
                    $this->cancelSupportProcess->processRevokedSelfPayCharges($request, $checkUser, $getCashAssessment, $getMedsysCashAssessment);
                }
            }
            $this->dbTransactionController->handleDatabaseTransactionProcess('commit');
            return response()->json(['message' => 'Charges successfully revoked'], 200);

        } catch (Exception $e) {
            $this->dbTransactionController->handleDatabaseTransactionProcess('rollback');
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
