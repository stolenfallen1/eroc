<?php

namespace App\Models\MMIS\procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'purchaseRequestMaster';

    public function purchaseRequestAttachments(){
        return $this->hasMany(PurchaseRequestAttachment::class, 'pr_request_id', 'id');
    }

    public function purchaseRequestDetails(){
        return $this->hasMany(PurchaseRequestDetails::class, 'pr_request_id', 'id');
    }
}