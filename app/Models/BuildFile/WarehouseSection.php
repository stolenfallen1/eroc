<?php

namespace App\Models\BuildFile;

use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseSection extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv";
    protected $table = "warehousesections";
    protected $guarded = [];

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'warehouse_id', 'id');
    }

}
