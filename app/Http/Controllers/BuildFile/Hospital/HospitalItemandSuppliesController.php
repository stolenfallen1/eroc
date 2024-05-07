<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\SearchFilter\Items;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\MMIS\inventory\ItemModel;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;

class HospitalItemandSuppliesController extends Controller
{
    public function index()
    {
        try {
            $data = Itemmasters::query();
            $data->with('itemGroup', 'itemCategory', 'unit');
            if(Request()->item_group_id) {
                $data->where('item_InventoryGroup_Id', Request()->item_group_id);
            }
            if(Request()->keyword) {
                $data->where('item_name', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('isactive', 'desc')->orderBy('id', 'asc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function report_count() {
        try {
            
            $inventoryGroups = [
                2 => "DRUGS AND MEDICINES",
                1 => "SUPPLIES",
                3 => "ASSETS",
                4 => "EQUIPMENTS",
                6 => "OTHERS"
            ];

            $groupCounts = [];

            foreach ($inventoryGroups as $groupId => $groupName) {
                $count = Itemmasters::where('item_InventoryGroup_Id', $groupId)
                                    ->where('isActive', 1)
                                    ->count();

                if ($groupId != 3 && $groupId != 4) {
                    $groupCounts[] = [
                        "id" => $groupId,
                        "name" => $groupName,
                        "total" => $count
                    ];
                }
            }
            $combinedAssetsEquipmentsCount = Itemmasters::whereIn('item_InventoryGroup_Id', [3, 4])
                                                ->where('isActive', 1)
                                                ->count();
    

            array_unshift($groupCounts, [
                "id" => "3_4",
                "name" => "ASSETS & EQUIPMENTS",
                "total" => $combinedAssetsEquipmentsCount
            ]);
            usort($groupCounts, function ($a, $b) use ($inventoryGroups) {
                // Get the position of group IDs in the inventoryGroups array
                $orderA = array_search($a['id'], array_keys($inventoryGroups));
                $orderB = array_search($b['id'], array_keys($inventoryGroups));
                return $orderA <=> $orderB;
            });
            return response()->json($groupCounts, 200);
    
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    
    

    public function search()
    {
        try {
            $data = Itemmasters::with('itemGroup', 'itemCategory', 'unit');
            if(Request()->itemcode) {
                $data->where('id', Request()->itemcode);
            }
            if(Request()->itemname) {
                $data->where('item_name', 'LIKE', '%'.Request()->itemname.'%');
            }

            $data->orderBy('id', 'desc');
            $data->where('isActive', '1');
            $data->offset(0,300);
            return response()->json($data->get(), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function store(Request $request)
    {

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $payload = $request->payload;
            $item = Itemmasters::create([
                'item_name' =>  isset($payload['item_name']) ? $payload['item_name'] : null,
                'item_Description' =>  isset($payload['item_Description']) ? $payload['item_Description'] : null,
                'item_Brand_Id' => (int) isset($payload['item_Brand_Id']) ? $payload['item_Brand_Id'] : null,
                'item_Manufacturer_Id' => (int) isset($payload['item_Manufacturer_Id']) ? $payload['item_Manufacturer_Id'] : null,
                'item_specification' =>  isset($payload['item_specification']) ? $payload['item_specification'] : null,
                'item_SKU' =>  isset($payload['item_SKU']) ? $payload['item_SKU'] : null,
                'item_Barcode' =>  isset($payload['item_Barcode']) ? $payload['item_Barcode'] : null,
                'item_UnitOfMeasure_Id' => (int) isset($payload['item_UnitOfMeasure_Id']) ? $payload['item_UnitOfMeasure_Id'] : null,
                'item_InventoryGroup_Id' => (int) isset($payload['item_InventoryGroup_Id']) ? $payload['item_InventoryGroup_Id'] : null,
                'item_Med_Dosage_Form_id' => (int) isset($payload['item_Med_Dosage_Form_id']) ? $payload['item_Med_Dosage_Form_id'] : null,
                'item_Med_Drug_Administration_Route_Id' => (int) isset($payload['item_Med_Drug_Administration_Route_Id']) ? $payload['item_Med_Drug_Administration_Route_Id'] : null,
                'item_Category_Id' => (int) isset($payload['item_Category_Id']) ? $payload['item_Category_Id'] : null,
                'item_SubCategory_Id' => (int) isset($payload['item_SubCategory_Id']) ? $payload['item_SubCategory_Id'] : null,
                'item_Model_No' =>  isset($payload['item_Model_No']) ? $payload['item_Model_No'] : null,
                'item_Med_AntibioticClass_Id' => (int) isset($payload['item_Med_AntibioticClass_Id']) ? $payload['item_Med_AntibioticClass_Id'] : null,
                'item_Med_GenericName_Id' => (int) isset($payload['item_Med_GenericName_Id']) ? $payload['item_Med_GenericName_Id'] : null,
                'item_Med_TherapeuticClass_Id' => (int) isset($payload['item_Med_TherapeuticClass_Id']) ? $payload['item_Med_TherapeuticClass_Id'] : null,
                'item_Med_Prescription' =>  isset($payload['item_Med_Prescription']) ? $payload['item_Med_Prescription'] : null,
                'item_Med_Indication' =>  isset($payload['item_Med_Indication']) ? $payload['item_Med_Indication'] : null,
                'item_Med_Dosage' =>  isset($payload['item_Med_Dosage']) ? $payload['item_Med_Dosage'] : null,
                'item_Med_Precaution' =>  isset($payload['item_Med_Precaution']) ? $payload['item_Med_Precaution'] : null,
                'item_Med_AdverseReaction' =>  isset($payload['item_Med_AdverseReaction']) ? $payload['item_Med_AdverseReaction'] : null,
                'item_Med_Interaction' =>  isset($payload['item_Med_Interaction']) ? $payload['item_Med_Interaction'] : null,
                'item_Med_Reconstitution' =>  isset($payload['item_Med_Reconstitution']) ? $payload['item_Med_Reconstitution'] : null,
                'item_Med_Stability' =>  isset($payload['item_Med_Stability']) ? $payload['item_Med_Stability'] : null,
                'item_Med_Storage' =>  isset($payload['item_Med_Storage']) ? $payload['item_Med_Storage'] : null,
                'item_Med_Preparation' =>  isset($payload['item_Med_Preparation']) ? $payload['item_Med_Preparation'] : null,
                // 'item_Med_DrugAdministration_Id'=> (int)  isset($payload['item_Med_DrugAdministration_Id']) ? $payload['item_Med_DrugAdministration_Id'] : null,
                'item_Med_DOH_Code' =>  isset($payload['item_Med_DOH_Code']) ? $payload['item_Med_DOH_Code'] : null,
                'item_Remarks' =>  isset($payload['item_Remarks']) ? $payload['item_Remarks'] : null,
                'isSupplies' => (int) isset($payload['isSupplies']) ? $payload['isSupplies'] : null,
                'isMedicines' => (int) isset($payload['isMedicines']) ? $payload['isMedicines'] : null,
                'isFixedAsset' => (int) isset($payload['isFixedAsset']) ? $payload['isFixedAsset'] : null,
                'isReagents' => (int) isset($payload['isReagents']) ? $payload['isReagents'] : null,
                'isMDRP' => (int)  isset($payload['isMDRP']) ? $payload['isMDRP'] : null,
                'isConsignment' => (int) isset($payload['isConsignment']) ? $payload['isConsignment'] : null,
                'isSerialNo_Required' => (int) isset($payload['isSerialNo_Required']) ? $payload['isSerialNo_Required'] : null,
                'isLotNo_Required' => (int) isset($payload['isLotNo_Required']) ? $payload['isLotNo_Required'] : null,
                'isExpiryDate_Required' => (int) isset($payload['isExpiryDate_Required']) ? $payload['isExpiryDate_Required'] : null,
                'isForProduction' => (int)  isset($payload['isForProduction']) ? $payload['isForProduction'] : null,
                'isPersihable' => (int)  isset($payload['isPerishable']) ? $payload['isPerishable'] : null,
                'isVatable' => (int) isset($payload['isVatable']) ? $payload['isVatable'] : null,
                'isVatExempt' => (int) isset($payload['isVatExempt']) ? $payload['isVatExempt'] : null,
                'isAllowDiscount' => (int)  isset($payload['isAllowDiscount']) ? $payload['isAllowDiscount'] : null,
                'isZeroRated' => (int) isset($payload['isZeroRated']) ? $payload['isZeroRated'] : null,
                'isOpenPrice' => (int) isset($payload['isOpenPrice']) ? $payload['isOpenPrice'] : null,
                'isAllowStatOrder' => (int) isset($payload['isAllowStatOrder']) ? $payload['isAllowStatOrder'] : null,
                'item_StatPercent' => (int) isset($payload['item_StatPercent']) ? $payload['item_StatPercent'] : null,
                'isIncludeInStatement' => (int) isset($payload['isIncludeInStatement']) ? $payload['isIncludeInStatement'] : null,
                'created_at' => Carbon::now(),
                'isActive' => (int) '1'
            ]);
            if(Auth()->user()->warehouse_id && Auth()->user()->branch_id) {
                $warehourse_item = $item->wareHouseItems()->create([
                    'warehouse_Id' => Auth()->user()->warehouse_id,
                    'branch_id' => Auth()->user()->branch_id,
                    'item_UnitofMeasurement_Id' => (int)  $payload['item_UnitOfMeasure_Id'],
                    'item_ListCost' => '0',
                    'isReOrder' => '0',
                    'isLotNo_Required' => '0',
                    'created_at' => Carbon::now(),
                    // 'DateCreated' => Carbon::now(),
                    'isActive' => '1',
                    'CreatedBy' => Auth()->user()->idnumber,
                ]);
                $sequence = SystemSequence::where('seq_description', 'like', '%Inventory Transaction Code Reference%')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                $transaction = FmsTransactionCode::where('transaction_description', 'like', '%Beginning Inventory%')->where('isActive', 1)->first();
                InventoryTransaction::create([
                    'branch_Id' => Auth::user()->branch_id,
                    'warehouse_Group_Id' => Auth()->user()->warehouse->warehouseGroup->id,
                    'warehouse_Id' => Auth()->user()->warehouse_id,
                    'transaction_Item_Id' => $item->id,
                    'transaction_Item_Barcode' => $item->item_Barcode,
                    'transaction_Date' => Carbon::now(),
                    'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                    'transaction_Item_UnitofMeasurement_Id' => $item->item_UnitOfMeasure_Id,
                    'transaction_Qty' => $warehourse_item->item_OnHand,
                    'transaction_Item_OnHand' => $warehourse_item->item_OnHand,
                    'transaction_Item_ListCost' => $warehourse_item->item_ListCost,
                    'transaction_UserID' =>  Auth::user()->idnumber,
                    'createdBy' =>  Auth::user()->idnumber,
                    'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                ]);
                $sequence->update([
                    'seq_no' => (int) $sequence->seq_no + 1,
                    'recent_generated' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                ]);
            }
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
        return response()->json(["message" => "success"], 200);
    }


    public function update(Request $request, $id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $payload = $request->payload;
            $items = Itemmasters::with('wareHouseItems')->where('id', $id)->first();
            $items->update([
                'item_name' =>  isset($payload['item_name']) ? $payload['item_name'] : null,
                'item_Description' =>  isset($payload['item_Description']) ? $payload['item_Description'] : null,
                'item_Brand_Id' => (int) isset($payload['item_Brand_Id']) ? $payload['item_Brand_Id'] : null,
                'item_Manufacturer_Id' => (int) isset($payload['item_Manufacturer_Id']) ? $payload['item_Manufacturer_Id'] : null,
                'item_specification' =>  isset($payload['item_specification']) ? $payload['item_specification'] : null,
                'item_SKU' =>  isset($payload['item_SKU']) ? $payload['item_SKU'] : null,
                'item_Barcode' =>  isset($payload['item_Barcode']) ? $payload['item_Barcode'] : null,
                'item_UnitOfMeasure_Id' => (int) isset($payload['item_UnitOfMeasure_Id']) ? $payload['item_UnitOfMeasure_Id'] : null,
                'item_InventoryGroup_Id' => (int) isset($payload['item_InventoryGroup_Id']) ? $payload['item_InventoryGroup_Id'] : null,
                'item_Med_Dosage_Form_id' => (int) isset($payload['item_Med_Dosage_Form_id']) ? $payload['item_Med_Dosage_Form_id'] : null,
                'item_Med_Drug_Administration_Route_Id' => (int) isset($payload['item_Med_Drug_Administration_Route_Id']) ? $payload['item_Med_Drug_Administration_Route_Id'] : null,
                'item_Category_Id' => (int) isset($payload['item_Category_Id']) ? $payload['item_Category_Id'] : null,
                'item_SubCategory_Id' => (int) isset($payload['item_SubCategory_Id']) ? $payload['item_SubCategory_Id'] : null,
                'item_Model_No' =>  isset($payload['item_Model_No']) ? $payload['item_Model_No'] : null,
                'item_Med_AntibioticClass_Id' => (int) isset($payload['item_Med_AntibioticClass_Id']) ? $payload['item_Med_AntibioticClass_Id'] : null,
                'item_Med_GenericName_Id' => (int) isset($payload['item_Med_GenericName_Id']) ? $payload['item_Med_GenericName_Id'] : null,
                'item_Med_TherapeuticClass_Id' => (int) isset($payload['item_Med_TherapeuticClass_Id']) ? $payload['item_Med_TherapeuticClass_Id'] : null,
                'item_Med_Prescription' =>  isset($payload['item_Med_Prescription']) ? $payload['item_Med_Prescription'] : null,
                'item_Med_Indication' =>  isset($payload['item_Med_Indication']) ? $payload['item_Med_Indication'] : null,
                'item_Med_Dosage' =>  isset($payload['item_Med_Dosage']) ? $payload['item_Med_Dosage'] : null,
                'item_Med_Precaution' =>  isset($payload['item_Med_Precaution']) ? $payload['item_Med_Precaution'] : null,
                'item_Med_AdverseReaction' =>  isset($payload['item_Med_AdverseReaction']) ? $payload['item_Med_AdverseReaction'] : null,
                'item_Med_Interaction' =>  isset($payload['item_Med_Interaction']) ? $payload['item_Med_Interaction'] : null,
                'item_Med_Reconstitution' =>  isset($payload['item_Med_Reconstitution']) ? $payload['item_Med_Reconstitution'] : null,
                'item_Med_Stability' =>  isset($payload['item_Med_Stability']) ? $payload['item_Med_Stability'] : null,
                'item_Med_Storage' =>  isset($payload['item_Med_Storage']) ? $payload['item_Med_Storage'] : null,
                'item_Med_Preparation' =>  isset($payload['item_Med_Preparation']) ? $payload['item_Med_Preparation'] : null,
                // 'item_Med_DrugAdministration_Id'=> (int)  isset($payload['item_Med_DrugAdministration_Id']) ? $payload['item_Med_DrugAdministration_Id'] : null,
                'item_Med_DOH_Code' =>  isset($payload['item_Med_DOH_Code']) ? $payload['item_Med_DOH_Code'] : null,
                'item_Remarks' =>  isset($payload['item_Remarks']) ? $payload['item_Remarks'] : null,
                'isSupplies' => (int) isset($payload['isSupplies']) ? $payload['isSupplies'] : null,
                'isMedicines' => (int) isset($payload['isMedicines']) ? $payload['isMedicines'] : null,
                'isFixedAsset' => (int) isset($payload['isFixedAsset']) ? $payload['isFixedAsset'] : null,
                'isReagents' => (int) isset($payload['isReagents']) ? $payload['isReagents'] : null,
                'isMDRP' => (int)  isset($payload['isMDRP']) ? $payload['isMDRP'] : null,
                'isConsignment' => (int) isset($payload['isConsignment']) ? $payload['isConsignment'] : null,
                'isSerialNo_Required' => (int) isset($payload['isSerialNo_Required']) ? $payload['isSerialNo_Required'] : null,
                'isLotNo_Required' => (int) isset($payload['isLotNo_Required']) ? $payload['isLotNo_Required'] : null,
                'isExpiryDate_Required' => (int) isset($payload['isExpiryDate_Required']) ? $payload['isExpiryDate_Required'] : null,
                'isForProduction' => (int)  isset($payload['isForProduction']) ? $payload['isForProduction'] : null,
                'isPersihable' => (int)  isset($payload['isPerishable']) ? $payload['isPerishable'] : null,
                'isVatable' => (int) isset($payload['isVatable']) ? $payload['isVatable'] : null,
                'isVatExempt' => (int) isset($payload['isVatExempt']) ? $payload['isVatExempt'] : null,
                'isAllowDiscount' => (int)  isset($payload['isAllowDiscount']) ? $payload['isAllowDiscount'] : null,
                'isZeroRated' => (int) isset($payload['isZeroRated']) ? $payload['isZeroRated'] : null,
                'isOpenPrice' => (int) isset($payload['isOpenPrice']) ? $payload['isOpenPrice'] : null,
                'isAllowStatOrder' => (int) isset($payload['isAllowStatOrder']) ? $payload['isAllowStatOrder'] : null,
                'item_StatPercent' => (int) isset($payload['item_StatPercent']) ? $payload['item_StatPercent'] : null,
                'isIncludeInStatement' => (int) isset($payload['isIncludeInStatement']) ? $payload['isIncludeInStatement'] : null,
                // 'DateModified' => Carbon::now(),
                // 'ModifiedBy' => Auth()->user()->idnumber,
                'isActive' => (int) '1'
            ]);
            // $items->wareHouseItems()->where('item_Id',$items->id)->update([
            //     // 'warehouse_Id' => Auth()->user()->warehouse_id,
            //     // 'warehuse_Group_Id' => Auth()->user()->id,
            //     'item_UnitofMeasurement_Id' => (int) $items->item_UnitofMeasurement_Id,
            //     'item_ListCost' =>'0',
            //     'isReOrder' =>'0',
            //     'isLotNo_Required' =>'0',
            //     'ModifiedDate' => Carbon::now(),
            //     'isActive' =>'1',
            //     'ModifiedBy'=>Auth()->user()->id,
            // ]);

            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
        return response()->json(["message" => "success"], 200);
    }
}
