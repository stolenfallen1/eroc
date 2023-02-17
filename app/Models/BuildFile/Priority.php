<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "invPriority";

    public function purchaseRequests(){
        return $this->hasMany(PurchaseRequest::class, 'pr_Priority_Id', 'id');
    }
}
