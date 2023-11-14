<?php

namespace App\Models\MMIS\inventory;

use App\Models\BuildFile\Warehouses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequisitionItemBatch extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.stock_requisition_item_batchs';
    protected $guarded = [];

    public function stockRequisitionItem(){
        return $this->belongsTo(StockRequisitionItem::class, 'stock_requisition_item_id', 'id');
    }

    public function batch(){
        return $this->belongsTo(ItemBatchModelMaster::class, 'batch_id', 'id');
    }

    public function sender(){
        return $this->belongsTo(Warehouses::class, 'sender_warehouse_id', 'id');
    }

    public function receiver(){
        return $this->belongsTo(Warehouses::class, 'receiver_warehouse_id', 'id');
    }
}
