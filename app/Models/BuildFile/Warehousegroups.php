<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehousegroups extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "warehousegroups";

    public function warehouses(){
        return $this->hasMany(Warehouses::class, 'warehouse_Group_Id', 'id');
    }
}
