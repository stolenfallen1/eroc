<?php

namespace App\Models\BuildFile;

use App\Models\BuildFile\ItemGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Itemcategories;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Unitofmeasurement;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Itemmasters extends Model
{
    use HasFactory;
    protected $table = 'invItemMaster';
    protected $connection = "sqlsrv";
    protected $guarded = [];
    
    public function wareHouseItems(){
        return $this->hasMany(Warehouseitems::class, 'item_Id', 'id');
    }

    public function wareHouseItem(){
        return $this->hasOne(Warehouseitems::class, 'item_Id', 'id')->where('warehouse_Id', Request()->warehouse_id);
    }

    public function purchaseRequest(){
        return $this->hasMany(PurchaseOrderDetails::class, 'item_Id', 'id');
    }

    public function itemGroup(){
        return $this->belongsTo(ItemGroup::class, 'item_InventoryGroup_Id');
    }

    public function itemCategory(){
        return $this->belongsTo(Itemcategories::class, 'item_Category_Id');
    }

    public function unit(){
        return $this->belongsTo(Unitofmeasurement::class, 'item_UnitOfMeasure_Id');
    }

    public function batchs(){
        return $this->hasMany(ItemBatch::class, 'item_Id', 'id');
    }


}
