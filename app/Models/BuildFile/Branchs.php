<?php

namespace App\Models\BuildFile;

use App\Models\MMIS\procurement\purchaseOrderMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branchs extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = 'branch';

    public function warehouses(){
        return $this->hasMany(Branches::class, 'warehouse_Branch_Id', 'id');
    }

    public function purchaseOrders(){
        return $this->hasMany(purchaseOrderMaster::class, 'po_Document_branch_id', 'id');
    }
}
