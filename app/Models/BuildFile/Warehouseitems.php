<?php

namespace App\Models\BuildFile;

use App\Models\BuildFile\Brands;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    public function unit()
    {
        return $this->belongsTo(Unitofmeasurement::class, 'item_UnitofMeasurement_Id', 'id');
    }
    
    
    
}
