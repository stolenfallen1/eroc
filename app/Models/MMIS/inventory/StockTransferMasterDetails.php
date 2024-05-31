<?php

namespace App\Models\MMIS\inventory;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatchModelMaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTransferMasterDetails extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_mmis';
    protected $table = 'CDG_MMIS.dbo.stockTransfersMasterDetail';
    protected $guarded = [];
    protected $appends = ['transfer_qty','received_qty'];
    protected $with = ['itemdetails','itemdetails.itemMaster','batchs','itemdetails.itemMaster.unit'];

    public function itemdetails()
    {
        return $this->belongsTo(Warehouseitems::class, 'transfer_item_id', 'item_Id');
    }
    public function batchs(){
        return $this->hasMany(ItemBatchModelMaster::class, 'id','transfer_item_batch_id');
    }
    public function getTransferQtyAttribute(){
        return (float) $this->transfer_item_qty - (float)$this->received_item_qty;
    }
    public function getReceivedQtyAttribute(){
        return (float)$this->received_item_qty;
    }
}
