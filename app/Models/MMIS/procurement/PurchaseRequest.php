<?php

namespace App\Models\MMIS\procurement;

use App\Models\Approver\invStatus;
use App\Models\BuildFile\Priority;
use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'purchaseRequestMaster';

    protected $guarded = [];
    // protected $fillable = [

    // ];

    public function purchaseRequestAttachments(){
        return $this->hasMany(PurchaseRequestAttachment::class, 'pr_request_id', 'id');
    }

    public function purchaseRequestDetails(){
        return $this->hasMany(PurchaseRequestDetails::class, 'pr_request_id', 'id');
    }

    public  function status(){
        return $this->belongsTo(invStatus::class, 'pr_Status_Id', 'id');
    }

    public  function priority(){
        return $this->belongsTo(Priority::class, 'pr_Priority_Id');
    }

    public  function warehouse(){
        return $this->belongsTo(Warehouses::class, 'warehouse_Id');
    }
}