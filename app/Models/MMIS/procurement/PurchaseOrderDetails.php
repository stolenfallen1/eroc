<?php

namespace App\Models\MMIS\procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetails extends Model
{
    use HasFactory;
    protected $table = 'purchaseOrderDetail';
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
}
