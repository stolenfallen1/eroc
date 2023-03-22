<?php

namespace App\Http\Controllers\BuildFile;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\SearchFilter\Items;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Warehouseitems;

class ItemandServicesController extends Controller
{
    public function index()
    {
        return (new Items)->searchable();
    }

    
    public function store(Request $request)
    {

        $item = Itemmasters::create([
            'item_name'=> $request->item_name ?? '',
            'item_Description'=> $request->item_Description ?? '',
            'item_Brand_Id'=> (int)$request->item_Brand_Id ?? '',
            'item_Manufacturer_Id'=> (int)$request->item_Manufacturer_Id ?? '',
            'item_specsification'=> $request->item_specsification ?? '',
            'item_SKU'=> $request->item_SKU ?? '',
            'item_Barcode'=> $request->item_Barcode ?? '',
            'item_UnitOfMeasure_Id'=> (int)$request->item_UnitOfMeasure_Id ?? '',
            'item_InventoryGroup_Id'=> (int)$request->item_InventoryGroup_Id ?? '',
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
            'item_Med_DrugAdministration_Id'=> (int) $request->item_Med_DrugAdministration_Id ?? '',
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
            'isPersihable'=>(int) $request->isPersihable ?? '',
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
        $item->wareHouseItems()->create([
            'warehouse_Id' => Auth()->user()->branch_id,
            'warehuse_Group_Id' => Auth()->user()->warehouse->warehouseGroup->id,
            'item_UnitofMeasurement_Id' => (int) $request->item_UnitOfMeasure_Id,
            'item_ListCost' =>'0',
            'isReOrder' =>'0',
            'isLotNo_Required' =>'0',
            'created_at' => Carbon::now(),
            'DateCreated' => Carbon::now(),
            'isActive' =>'1',
            'CreatedBy'=>Auth()->user()->id,
        ]);
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
            'item_specsification'=> $request->item_specsification ?? '',
            'item_SKU'=> $request->item_SKU ?? '',
            'item_Barcode'=> $request->item_Barcode ?? '',
            'item_UnitOfMeasure_Id'=> (int)$request->item_UnitOfMeasure_Id ?? '',
            'item_InventoryGroup_Id'=> (int)$request->item_InventoryGroup_Id ?? '',
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
            'item_Med_DrugAdministration_Id'=> (int) $request->item_Med_DrugAdministration_Id ?? '',
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
            'isPersihable'=>(int) $request->isPersihable ?? '',
            'isVatable'=> (int)$request->isVatable ?? '',
            'isVatExempt'=> (int)$request->isVatExempt ?? '',
            'isAllowDiscount'=>(int) $request->isAllowDiscount ?? '',
            'isZeroRated'=> (int)$request->isZeroRated ?? '',
            'isOpenPrice'=> (int)$request->isOpenPrice ?? '',
            'isAllowStatOrder'=> (int)$request->isAllowStatOrder ?? '',
            'item_StatPercent'=> (int)$request->item_StatPercent ?? '',
            'isIncludeInStatement'=> (int)$request->isIncludeInStatement ?? '',
            'DateModified' => Carbon::now(),
            'ModifiedBy'=>Auth()->user()->id,
            'isActive'=> (int) '1'
        ]);
        $items->wareHouseItems()->where('item_Id',$items->id)->update([
            'warehouse_Id' => Auth()->user()->id,
            'warehuse_Group_Id' => Auth()->user()->id,
            'item_UnitofMeasurement_Id' => (int) $items->item_UnitofMeasurement_Id,
            'item_ListCost' =>'0',
            'isReOrder' =>'0',
            'isLotNo_Required' =>'0',
            'ModifiedDate' => Carbon::now(),
            'isActive' =>'1',
            'ModifiedBy'=>Auth()->user()->id,
        ]);
        return response()->json(["message" => "success"], 200);
    }

    public function destroy($id)
    {
        
        $items = Itemmasters::with('wareHouseItems')->where('id', $id)->first();
        $items->wareHouseItems()->where('item_Id',$items->id)->delete();
        $items->delete();
        return response()->json(["message" => "success"], 200);
    }

    public function addToLocation($id)
    {
        $item = Itemmasters::findOrfail($id);
        $item->wareHouseItems()->create([
            'warehouse_Id' => Auth()->user()->branch_id,
            'warehuse_Group_Id' => Auth()->user()->warehouse->warehouseGroup->id,
            'item_UnitofMeasurement_Id' => (int) $item->item_UnitOfMeasure_Id,
            'item_ListCost' =>'0',
            'isReOrder' =>'0',
            'isLotNo_Required' =>'0',
            'created_at' => Carbon::now(),
            'DateCreated' => Carbon::now(),
            'isActive' =>'1',
            'CreatedBy'=>Auth()->user()->id,
        ]);
        return response()->json(["message" => "success"], 200);
    }
}