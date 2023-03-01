<?php

namespace App\Models\MMIS\procurement;

use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestDetails extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'purchaseRequestDetail';
    protected $guarded = [];

    public function purchaseRequest(){
        return $this->belongsTo(PurchaseRequest::class, 'pr_request_id');
    }

    public function itemMaster(){
        return $this->belongsTo(Itemmasters::class, 'item_Id');
    }

    public function canvases(){
        return $this->hasMany(CanvasMaster::class, 'pr_request_details_id', 'id');
    }
}
