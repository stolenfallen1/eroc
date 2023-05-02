<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Branchs;
use App\Models\BuildFile\Warehouses;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'RRMaster';

    protected $guarded = [];

    public function branch(){
        return $this->belongsTo(Branchs::class, 'rr_Document_Branch_Id');
    }

    public function purchaseOrder(){
        return $this->belongsTo(purchaseOrderMaster::class, 'po_id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'rr_Document_Warehouse_Id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryItems::class, 'rr_id', 'id');
    }
}
