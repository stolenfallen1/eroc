<?php

namespace App\Models\Approver;

use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Model;


class InvStatus extends Model
{
    protected $table = 'mscStatus';
    
    protected $connection = "sqlsrv";
    protected $guarded = [];

    public function purchaseRequests(){
        return $this->hasMany(PurchaseRequest::class, 'pr_Status_Id', 'id');
    }

    public function purchaseOrders(){
        return $this->hasMany(purchaseOrderMaster::class, 'po_status_id', 'id');
    }
}
