<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderConsignmentItem extends Model
{
    use HasFactory;
    protected $connection = "sqlsrv_mmis";
    protected $table = 'purchaseOrderConsignmentItem';

    protected $guarded = [];

   
    public function itemdetails()
    {
        return $this->belongsTo(Itemmasters::class, 'request_item_id');
    }

    public function batchs()
    {
        return $this->belongsTo(ItemBatchModelMaster::class, 'batch_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(Unitofmeasurement::class, 'item_unitofmeasurement_id','id');
    }
}
