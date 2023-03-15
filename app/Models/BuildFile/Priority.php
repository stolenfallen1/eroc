<?php

namespace App\Models\BuildFile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Priority extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "mscPriority";

    public function purchaseRequests(){
        return $this->hasMany(PurchaseRequest::class, 'pr_Priority_Id', 'id');
    }
}
