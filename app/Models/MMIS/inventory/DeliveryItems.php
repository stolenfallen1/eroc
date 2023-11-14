<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryItems extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'RRDetail';

    protected $guarded = [];

    public function batchs()
    {
        return $this->hasMany(ItemBatchModelMaster::class, 'delivery_item_id', 'id');
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'rr_id');
    }

    public function item()
    {
        return $this->belongsTo(Itemmasters::class, 'rr_Detail_Item_Id');
    }

    public function unit()
    {
        return $this->belongsTo(Unitofmeasurement::class, 'rr_Detail_Item_UnitofMeasurement_Id_Received');
    }
}
