<?php

namespace App\Models\MMIS\procurement;

use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderDetails extends Model
{
    use HasFactory;
    protected $table = 'CDG_MMIS.dbo.purchaseOrderDetail';
    protected $connection = 'sqlsrv_mmis';

    protected $guarded = [];

    public function purchaseOrder(){
        return $this->belongsTo(purchaseOrderMaster::class, 'po_id');
    }

    public function canvas(){
        return $this->belongsTo(CanvasMaster::class, 'canvas_id');
    }

    public function purchaseRequestDetail(){
        return $this->belongsTo(PurchaseRequestDetails::class, 'pr_detail_id');
    }
    
    public function item(){
        return $this->belongsTo(Itemmasters::class, 'po_Detail_item_id');
    }

    public function unit(){
        return $this->belongsTo(Unitofmeasurement::class, 'po_Detail_item_unitofmeasurement_id');
    }
}
