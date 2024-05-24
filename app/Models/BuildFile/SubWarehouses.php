<?php

namespace App\Models\BuildFile;

use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubWarehouses extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv";
    protected $table = "sub_warehouses";
    protected $guarded = [];

    protected $with = ['warehouseDetails'];
    
    public function warehouseDetails(){
        return $this->belongsTo(Warehouses::class, 'sub_warehouse_id');
    }
}
