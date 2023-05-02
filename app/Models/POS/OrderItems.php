<?php

namespace App\Models\POS;

use App\Models\BuildFile\Brands;
use App\Models\BuildFile\Warehouses;
use App\Models\POS\vwWarehouseItems;
use App\Models\BuildFile\Itemmasters;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItems extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.orderItems';
    protected $guarded = [];
    protected $with = ['Warehouseitems.itemMaster'];



    public function Warehouseitems(){
        return $this->belongsTo(Warehouseitems::class,'order_item_id', 'id');
    }

    public function ItemBatch(){
        return $this->belongsTo(ItemBatch::class,'order_item_batchno', 'id');
    }

    public function vwItem_details(){
        return $this->belongsTo(vwWarehouseItems::class,'order_item_id', 'id');
    }
  
}
