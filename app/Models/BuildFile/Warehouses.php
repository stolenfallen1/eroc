<?php

namespace App\Models\BuildFile;

use App\Models\BuildFile\SubWarehouses;
use App\Models\MMIS\inventory\Delivery;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\BuildFile\WarehouseSection;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouses extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "warehouses";
    protected $guarded = [];

    public function purchaseRequest(){
        return $this->hasMany(PurchaseRequest::class, 'warehouse_Id', 'id');
    }

    public function branch(){
        return $this->belongsTo(Branchs::class, 'warehouse_Branch_Id');
    }

    public function warehouseGroup(){
        return $this->belongsTo(Warehousegroups::class, 'warehouse_Group_Id');
    }

    public function itemGroups(){
        return $this->belongsToMany(ItemGroup::class, 'invItemInventoryGroup_mappings', 'warehouse_id', 'ItemInventoryGroup_id');
    }

    public function batchs(){
        return $this->hasMany(ItemBatchModelMaster::class, 'warehouse_id', 'id');
    }

    public function purchaseOrders(){
        return $this->hasMany(purchaseOrderMaster::class, 'po_Document_warehouse_id', 'id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'rr_Document_Warehouse_Id', 'id');
    }

    public function sections()
    {
        return $this->hasMany(WarehouseSection::class, 'warehouse_id', 'id');
    }

    public function subWarehouse()
    {
        return $this->hasMany(SubWarehouses::class, 'warehouse_id', 'id');
    }

}
