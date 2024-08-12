<?php

namespace App\Models\BuildFile;

use App\Models\BuildFile\ItemGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Itemcategories;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Unitofmeasurement;
use App\Models\MMIS\inventory\DeliveryItems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemMasterByLocation extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'CDG_MMIS.dbo.warehouseitems';
    protected $guarded = [];
    
    // public function wareHouseItems(){
    //     return $this->hasMany(Warehouseitems::class, 'item_Id', 'id');
    // }

    // public function wareHouseItem(){
    //     // $warehouse = Request()->warehouse_idd ?? Auth::user()->warehouse_id;
    //     $warehouse = isset(Request()->warehouse_idd) ? Request()->warehouse_idd : Auth::user()->warehouse_id;
    //     return $this->hasOne(Warehouseitems::class, 'item_Id', 'id')->where('warehouse_Id', $warehouse);
    // }
    // public function authWarehouseItem(){
    //     return $this->hasOne(Warehouseitems::class, 'item_Id', 'id')
    //         ->where('warehouse_Id', Auth::user()->warehouse_id)->where('branch_id', Auth::user()->branch_id);
    // }

    // public function purchaseRequest(){
    //     return $this->hasMany(PurchaseOrderDetails::class, 'item_Id', 'id');
    // }

    // public function itemGroup(){
    //     return $this->belongsTo(ItemGroup::class, 'item_InventoryGroup_Id');
    // }

    // public function itemCategory(){
    //     return $this->belongsTo(Itemcategories::class, 'item_Category_Id');
    // }

    // public function subcategory(){
    //     return $this->belongsTo(Itemsubcategories::class, 'item_SubCategory_Id');
    // }

    // public function unit(){
    //     return $this->belongsTo(Unitofmeasurement::class, 'item_UnitOfMeasure_Id');
    // }

    // public function brand()
    // {
    //     return $this->belongsTo(Brands::class, 'item_Brand_Id', 'id');
    // }
    // public function batchs(){
    //     return $this->hasMany(ItemBatchModelMaster::class, 'item_Id', 'id')->where('warehouse_id',Auth::user()->warehouse_id)->where('isConsumed','!=',1);
    // }

    // public function deliveryItem()
    // {
    //     return $this->hasMany(DeliveryItems::class, 'rr_Detail_Item_Id', 'id');
    // }

    

}
