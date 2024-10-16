<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReturnDetails extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.purchaseReturnDetails';
    protected $guarded = [];
    protected $with = ['batch','details','unit'];

    public function batch()
    {
        return $this->belongsTo(ItemBatchModelMaster::class, 'returned_item_batch_id','id');
    }

    public function details()
    {
        return $this->belongsTo(Itemmasters::class, 'returned_item_id','id');
    }

    public function unit()
    {
        return $this->belongsTo(Unitofmeasurement::class, 'returned_item_unitofmeasurement_id','id');
    }
}
