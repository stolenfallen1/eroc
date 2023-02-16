<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouses extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "warehouses";

    public function purchaseRequest(){
        return $this->hasMany(PurchaseRequest::class, 'warehouse_Id', 'id');
    }
}
