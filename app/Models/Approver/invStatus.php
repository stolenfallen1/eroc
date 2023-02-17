<?php

namespace App\Models\Approver;

use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Model;


class invStatus extends Model
{
    protected $connection = "sqlsrv";
    protected $table = 'invStatus';

    public function purchaseRequests(){
        return $this->hasMany(PurchaseRequest::class, 'pr_Status_Id', 'id');
    }
}
