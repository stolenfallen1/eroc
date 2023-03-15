<?php

namespace App\Models\Approver;

use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Model;


class InvStatus extends Model
{
    protected $connection = "sqlsrv";
    protected $table = 'mscStatus';

    public function purchaseRequests(){
        return $this->hasMany(PurchaseRequest::class, 'pr_Status_Id', 'id');
    }
}
