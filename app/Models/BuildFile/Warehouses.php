<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\inventory\ItemBatch;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouses extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "warehouses";

    public function purchaseRequest(){
        return $this->hasMany(PurchaseRequest::class, 'warehouse_Id', 'id');
    }

    public function branch(){
        return $this->belongsTo(Branchs::class, 'warehouse_Branch_Id');
    }

    public function warehouseGroup(){
        return $this->belongsTo(Warehousegroups::class, 'warehouse_Group_Id');
    }

    public function batchs(){
        return $this->hasMany(ItemBatch::class, 'warehouse_id', 'id');
    }

    public function purchaseOrders(){
        return $this->hasMany(purchaseOrderMaster::class, 'po_Document_branch_id', 'id');
    }


}
