<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Warehouses;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\VwDeliveryDetails;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VwDeliveryMaster extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'VwDeliveryMaster';

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(VwDeliveryDetails::class, 'rr_id', 'id');
    }

    public function warehouse(){
        return $this->belongsTo(Warehouses::class, 'rr_Document_Warehouse_Id');
    }
    
}
