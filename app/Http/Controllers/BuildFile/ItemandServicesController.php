<?php

namespace App\Http\Controllers\BuildFile;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\RecomputePrice;
use Illuminate\Support\Facades\DB;
use App\Helpers\SearchFilter\Items;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\MMIS\inventory\ItemModel;
use App\Helpers\SearchFilter\ItemLocation;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;

class ItemandServicesController extends Controller
{
    public function indexLocation()
    {
        return (new ItemLocation)->searchable();
    }
    public function index()
    {
        return (new Items)->searchable();
    }
    
    public function checkNameDuplication(Request $request)
    {
        return Itemmasters::where('item_name', 'like', '%' . $request->name . '%')->where('item_InventoryGroup_Id', $request->tab)->exists();
    }

    public function store(Request $request)
    {

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            $item = Itemmasters::create([
                'item_name'=> $request->item_name ?? '',
                'item_Description'=> $request->item_Description ?? '',
                'item_Brand_Id'=> (int)$request->item_Brand_Id ?? '',
                'item_Manufacturer_Id'=> (int)$request->item_Manufacturer_Id ?? '',
                'item_specification'=> $request->item_specification ?? '',
                'item_SKU'=> $request->item_SKU ?? '',
                'item_Barcode'=> $request->item_Barcode ?? '',
                'item_UPC'=> $request->item_UPC ?? '',
                'item_UnitOfMeasure_Id'=> (int)$request->item_UnitOfMeasure_Id ?? '',
                'item_InventoryGroup_Id'=> (int)$request->item_InventoryGroup_Id ?? '',
                'item_Med_Dosage_Form_id'=> (int)$request->item_Med_Dosage_Form_id ?? '',
                'item_Med_Drug_Administration_Route_Id'=> (int)$request->item_Med_Drug_Administration_Route_Id ?? '',
                'item_Category_Id'=> (int)$request->item_Category_Id ?? '',
                'item_SubCategory_Id'=> (int)$request->item_SubCategory_Id ?? '',
                'item_Model_No'=> $request->item_Model_No ?? '',
                'item_Med_AntibioticClass_Id'=> (int)$request->item_Med_AntibioticClass_Id ?? '',
                'item_Med_GenericName_Id'=> (int)$request->item_Med_GenericName_Id ?? '',
                'item_Med_TherapeuticClass_Id'=> (int)$request->item_Med_TherapeuticClass_Id ?? '',
                'item_Med_Prescription'=> $request->item_Med_Prescription ?? '',
                'item_Med_Indication'=> $request->item_Med_Indication ?? '',
                'item_Med_Dosage'=> $request->item_Med_Dosage ?? '',
                'item_Med_Precaution'=> $request->item_Med_Precaution ?? '',
                'item_Med_AdverseReaction'=> $request->item_Med_AdverseReaction ?? '',
                'item_Med_Interaction'=> $request->item_Med_Interaction ?? '',
                'item_Med_Reconstitution'=> $request->item_Med_Reconstitution ?? '',
                'item_Med_Stability'=> $request->item_Med_Stability ?? '',
                'item_Med_Storage'=> $request->item_Med_Storage ?? '',
                'item_Med_Preparation'=> $request->item_Med_Preparation ?? '',
                // 'item_Med_DrugAdministration_Id'=> (int) $request->item_Med_DrugAdministration_Id ?? '',
                'item_Med_DOH_Code'=> $request->item_Med_DOH_Code ?? '',
                'item_Remarks'=> $request->item_Remarks ?? '',
                'isSupplies'=> (int)$request->isSupplies ?? '',
                'isMedicines'=> (int)$request->isMedicines ?? '',
                'isFixedAsset'=> (int)$request->isFixedAsset ?? '',
                'isReagents'=> (int)$request->isReagents ?? '',
                'isMDRP'=>(int) $request->isMDRP ?? '',
                'isConsignment'=> (int)$request->isConsignment ?? '',
                'isSerialNo_Required'=> (int)$request->isSerialNo_Required ?? '',
                'isLotNo_Required'=> (int)$request->isLotNo_Required ?? '',
                'isExpiryDate_Required'=> (int)$request->isExpiryDate_Required ?? '',
                'isForProduction'=> (int) $request->isForProduction ?? '',
                'isPersihable'=>(int) $request->isPerishable ?? '',
                'isVatable'=> (int)$request->isVatable ?? '',
                'isVatExempt'=> (int)$request->isVatExempt ?? '',
                'isAllowDiscount'=>(int) $request->isAllowDiscount ?? '',
                'isZeroRated'=> (int)$request->isZeroRated ?? '',
                'isOpenPrice'=> (int)$request->isOpenPrice ?? '',
                'isAllowStatOrder'=> (int)$request->isAllowStatOrder ?? '',
                'item_StatPercent'=> (int)$request->item_StatPercent ?? '',
                'isIncludeInStatement'=> (int)$request->isIncludeInStatement ?? '',
                'created_at' => Carbon::now(),
                'isActive'=> (int) '1'
            ]);
            if(Auth()->user()->warehouse_id && Auth()->user()->branch_id){
                $warehourse_item = $item->wareHouseItems()->create([
                    'warehouse_Id' => Auth()->user()->warehouse_id,
                    'branch_id' => Auth()->user()->branch_id,
                    'item_UnitofMeasurement_Id' => (int) $request->item_UnitOfMeasure_Id,
                    'item_ListCost' =>'0',
                    'isReOrder' =>'0',
                    'isLotNo_Required' =>'0',
                    'created_at' => Carbon::now(),
                    // 'DateCreated' => Carbon::now(),
                    'isActive' =>'1',
                    'CreatedBy'=>Auth()->user()->idnumber,
                ]);
                $sequence = SystemSequence::where('seq_description', 'like', '%Inventory Transaction Code Reference%')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
                $transaction = FmsTransactionCode::where('description', 'like', '%Beginning Inventory%')->where('isActive', 1)->first();
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
                    'transaction_Acctg_TransType' =>  $transaction->code ?? '',
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
        $items = Itemmasters::with('wareHouseItems')->where('id', $id)->first();
        $items->update([
            'item_name'=> $request->item_name ?? '',
            'item_Description'=> $request->item_Description ?? '',
            'item_Brand_Id'=> (int)$request->item_Brand_Id ?? '',
            'item_Manufacturer_Id'=> (int)$request->item_Manufacturer_Id ?? '',
            'item_specification'=> $request->item_specification ?? '',
            'item_SKU'=> $request->item_SKU ?? '',
            'item_Barcode'=> $request->item_Barcode ?? '',
            'item_UPC'=> $request->item_UPC ?? '',
            'item_UnitOfMeasure_Id'=> (int)$request->item_UnitOfMeasure_Id ?? '',
            'item_InventoryGroup_Id'=> (int)$request->item_InventoryGroup_Id ?? '',
            'item_Med_Dosage_Form_id'=> (int)$request->item_Med_Dosage_Form_id ?? '',
            'item_Med_Drug_Administration_Route_Id'=> (int)$request->item_Med_Drug_Administration_Route_Id ?? '',
            'item_Category_Id'=> (int)$request->item_Category_Id ?? '',
            'item_SubCategory_Id'=> (int)$request->item_SubCategory_Id ?? '',
            'item_Model_No'=> $request->item_Model_No ?? '',
            'item_Med_AntibioticClass_Id'=> (int)$request->item_Med_AntibioticClass_Id ?? '',
            'item_Med_GenericName_Id'=> (int)$request->item_Med_GenericName_Id ?? '',
            'item_Med_TherapeuticClass_Id'=> (int)$request->item_Med_TherapeuticClass_Id ?? '',
            'item_Med_Prescription'=> $request->item_Med_Prescription ?? '',
            'item_Med_Indication'=> $request->item_Med_Indication ?? '',
            'item_Med_Dosage'=> $request->item_Med_Dosage ?? '',
            'item_Med_Precaution'=> $request->item_Med_Precaution ?? '',
            'item_Med_AdverseReaction'=> $request->item_Med_AdverseReaction ?? '',
            'item_Med_Interaction'=> $request->item_Med_Interaction ?? '',
            'item_Med_Reconstitution'=> $request->item_Med_Reconstitution ?? '',
            'item_Med_Stability'=> $request->item_Med_Stability ?? '',
            'item_Med_Storage'=> $request->item_Med_Storage ?? '',
            'item_Med_Preparation'=> $request->item_Med_Preparation ?? '',
            // 'item_Med_DrugAdministration_Id'=> (int) $request->item_Med_DrugAdministration_Id ?? '',
            'item_Med_DOH_Code'=> $request->item_Med_DOH_Code ?? '',
            'item_Remarks'=> $request->item_Remarks ?? '',
            'isSupplies'=> (int)$request->isSupplies ?? '',
            'isMedicines'=> (int)$request->isMedicines ?? '',
            'isFixedAsset'=> (int)$request->isFixedAsset ?? '',
            'isReagents'=> (int)$request->isReagents ?? '',
            'isMDRP'=>(int) $request->isMDRP ?? '',
            'isConsignment'=> (int)$request->isConsignment ?? '',
            'isSerialNo_Required'=> (int)$request->isSerialNo_Required ?? '',
            'isLotNo_Required'=> (int)$request->isLotNo_Required ?? '',
            'isExpiryDate_Required'=> (int)$request->isExpiryDate_Required ?? '',
            'isForProduction'=> (int) $request->isForProduction ?? '',
            'isPersihable'=>(int) $request->isPerishable ?? '',
            'isVatable'=> (int)$request->isVatable ?? '',
            'isVatExempt'=> (int)$request->isVatExempt ?? '',
            'isAllowDiscount'=>(int) $request->isAllowDiscount ?? '',
            'isZeroRated'=> (int)$request->isZeroRated ?? '',
            'isOpenPrice'=> (int)$request->isOpenPrice ?? '',
            'isAllowStatOrder'=> (int)$request->isAllowStatOrder ?? '',
            'item_StatPercent'=> (int)$request->item_StatPercent ?? '',
            'isIncludeInStatement'=> (int)$request->isIncludeInStatement ?? '',
            // 'DateModified' => Carbon::now(),
            // 'ModifiedBy'=>Auth()->user()->idnumber, 
            'isActive'=> (int) '1'
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
        return response()->json(["message" => "success"], 200);
    }

    public function destroy($id)
    {
        
        $items = Itemmasters::with('wareHouseItems')->where('id', $id)->first();
        $items->wareHouseItems()->where('item_Id',$items->id)->delete();
        $items->delete();
        return response()->json(["message" => "success"], 200);
    }

    public function addToLocation(Request $request, $id)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $item = Itemmasters::findOrfail($id);
            $onhand = 0;
            if($request->item_BatchNo_Id){
                if($request->isLotNo_Required){
                    $batch = ItemBatch::findOrfail($request->item_BatchNo_Id);
                    $onhand = $batch->item_Qty;
                }else{
                    $batch = ItemModel::findOrfail($request->item_ModelNo);
                    $onhand = $batch->item_Qty;
                }
            }
            $warehourse_item = $item->wareHouseItems()->create([
                'warehouse_Id' => Auth()->user()->warehouse_id,
                'branch_id' => Auth()->user()->branch_id,
                'item_UnitofMeasurement_Id' => (int) $item->item_UnitOfMeasure_Id,
                'isActive' => $request->isActive,
                'item_ListCost' => $request->item_ListCost ?? NULL,
                'item_AverageCost' => $request->item_AverageCost ?? NULL,
                'item_Markup_Out' => $request->item_Markup_Out ?? NULL,
                'item_Markup_In' => $request->item_Markup_In ?? NULL,
                'item_Selling_Price_Out' => $request->item_Selling_Price_Out ?? NULL,
                'item_Selling_Price_In' => $request->item_Selling_Price_In ?? NULL,
                'item_Minimum_StockLevel' => $request->item_Minimum_StockLevel,
                'item_Maximum_StockLevel' => $request->item_Maximum_StockLevel,
                'item_OnHand' => $onhand,
                'isExpiryDate_Required' => $request->isExpiryDate_Required,
                'isLotNo_Required' => $request->isLotNo_Required,
                'isModelNo_Required' => $request->isModelNo_Required,
                'isConsignment' => $request->isConsignment,
                'isReOrder' => '0',
                'created_at' => Carbon::now(),
                // 'DateCreated' => Carbon::now(),
                'CreatedBy'=>Auth()->user()->idnumber,
            ]);
            $sequence = SystemSequence::where('seq_description', 'like', '%Inventory Transaction Code Reference%')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
            $transaction = FmsTransactionCode::where('description', 'like', '%Beginning Inventory%')->where('isActive', 1)->first();
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
                'transaction_Acctg_TransType' =>  $transaction->code ?? '',
            ]);
            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
            ]);
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["message" => "success"], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }

    }

    public function updateToLocation(Request $request, $id)
    {
        $item = Warehouseitems::findOrfail($id);
        $item->update([
            // 'warehouse_Id' => Auth()->user()->warehouse_id,
            // 'branch_id' => Auth()->user()->branch_id,
            'isActive' => $request->isActive,
            'item_ListCost' => $request->item_ListCost ?? NULL,
            'item_AverageCost' => $request->item_AverageCost ?? NULL,
            'item_Markup_Out' => $request->item_Markup_Out ?? NULL,
            'item_Markup_In' => $request->item_Markup_In ?? NULL,
            'item_Selling_Price_Out' => $request->item_Selling_Price_Out ?? NULL,
            'item_Selling_Price_In' => $request->item_Selling_Price_In ?? NULL,
            'item_Minimum_StockLevel' => $request->item_Minimum_StockLevel,
            'item_Maximum_StockLevel' => $request->item_Maximum_StockLevel,
            'isExpiryDate_Required' => $request->isExpiryDate_Required,
            'isLotNo_Required' => $request->isLotNo_Required,
            'item_discount' => $request->item_discount,
            'isModelNo_Required' => $request->isModelNo_Required,
            'isConsignment' => $request->isConsignment,
            'ModifiedBy'=>Auth()->user()->idnumber,
        ]);
        (new RecomputePrice())->compute(Auth()->user()->warehouse_id,'',$id,'out');
        $item->itemMaster()->where('map_item_id',$request->map_item_id)->update([
            'isConsignment' => $request->isConsignment,
        ]);
        return response()->json(["message" => "success"], 200);
    }

    public function updatePhysicalCount(Request $request, Warehouseitems $warehouse_item){
        // return $warehouse_item;
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        
        try {
            $sequence = SystemSequence::where('seq_description', 'like', '%Inventory Transaction Code Reference%')->where('branch_id', Auth::user()->branch_id)->first(); // for inventory transaction only
            $transaction = FmsTransactionCode::where('description', 'like', '%Inventory Physical Count%')->where('isActive', 1)->first();

            ItemBatchModelMaster::where('id', $request['batch']['id'])->update([
                'item_Qty' => $request->item_OnHand,
             ]);
                
            $onhand = ItemBatchModelMaster::where(['warehouse_id' => $request['batch']['warehouse_id'], 'item_Id' => $warehouse_item->item_Id])->where('isConsumed', '!=', 1)->sum('item_Qty');

            // if($warehouse_item->isModelNo_Required){
            //     ItemModel::where('id', $request['model']['id'])->update([
            //         'item_Qty' => $request->item_OnHand,
            //     ]);
            // }else{
            //     ItemBatch::where('id', $request['batch']['id'])->update([
            //         'item_Qty' => $request->item_OnHand,
            //     ]);
            //     $onhand = ItemBatch::where(['warehouse_id' => $request['batch']['warehouse_id'], 'item_Id' => $warehouse_item->item_Id])->where('isConsumed', '!=', 1)->sum('item_Qty');
            // }
            $warehouse_item->update([
                'item_OnHand' => $onhand,
                'item_Last_Inventory_Count' => $warehouse_item->item_OnHand,
                'item_Manual_Count' => $request->item_OnHand,
                'ModifiedDate' => Carbon::now(),
                'ModifiedBy' => Auth::user()->idnumber,
            ]);

            InventoryTransaction::create([
                'branch_Id' => Auth::user()->branch_id,
                'warehouse_Group_Id' => Auth()->user()->warehouse->warehouseGroup->id,
                'warehouse_Id' => $request['batch']['warehouse_id'],
                'transaction_Item_Id' => $warehouse_item->item_Id,
                'transaction_Item_Barcode' => $request->item_Barcode,
                'transaction_Date' => Carbon::now(),
                'transaction_Item_Batch_Detail' => $request['batch']?$request['batch']['batch_Number']:NULL,
                'transaction_Item_Model_Number' => $request['model']?$request['model']['model_Number']:NULL,
                'trasanction_Reference_Number' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
                'transaction_Item_UnitofMeasurement_Id' => $warehouse_item->item_UnitOfMeasure_Id,
                'transaction_Qty' => $request->item_OnHand,
                'transaction_Item_OnHand' => $onhand,
                'transaction_Item_ListCost' => $warehouse_item->item_ListCost,
                'transaction_UserID' =>  $request->count_by ?? Auth::user()->idnumber,
                'createdBy' =>  Auth::user()->idnumber,
                'transaction_count_by' =>  $request->count_by ?? Auth::user()->idnumber,
                'transaction_Acctg_TransType' =>  $transaction->code ?? '',
            ]);
            (new RecomputePrice())->compute($request['batch']['warehouse_id'], '', $warehouse_item->item_Id, 'out');
            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($sequence->seq_prefix, $sequence->seq_no, $sequence->seq_suffix, ''),
            ]);
            
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["message" => "success"], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollBack();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }

    public function updateListCost(Request $request) {
        Warehouseitems::where('id', $request->warehouse_item_id)->update([
            'item_ListCost' => $request->item_ListCost,
            'item_Markup_In' => $request->item_Markup_In,
            'item_Markup_Out' => $request->item_Markup_Out,
            'item_Selling_Price_In' => $request->item_Selling_Price_In,
            'item_Selling_Price_Out' => $request->item_Selling_Price_Out
        ]);

        return response()->json(["message" => "success"], 200);
    }
}