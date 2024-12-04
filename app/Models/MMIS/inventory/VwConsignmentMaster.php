<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\VwConsignmentDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwConsignmentMaster extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'VwConsignmentMaster';
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(VwConsignmentDetails::class, 'rr_id', 'id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'rr_Document_Warehouse_Id');
    }

}
