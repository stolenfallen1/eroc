<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouseitems extends Model
{
    use HasFactory;

    protected $connection = "sqlsrv_mmis";
    protected $table = "warehouseitems";

    protected $guarded = [];

    public function itemMaster()
    {
        return $this->belongsTo(Itemmasters::class, 'item_Id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouses::class, 'warehouse_Id', 'id');
    }
}
