<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouseitems extends Model
{
    use HasFactory;

    protected $table = "CDG_MMIS.dbo.warehouseitems";
    protected $connection = "sqlsrv_mmis";

    protected $guarded = [];

    public function itemMaster()
    {
        return $this->belongsTo(Itemmasters::class, 'item_Id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouses::class, 'warehouse_Id', 'id');
    }
}
