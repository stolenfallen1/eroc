<?php

namespace App\Models\POS;

use App\Models\POS\vwWarehouseItems;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnDetailsTransaction extends Model
{   
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'refund_details';
    protected $guarded = [];

    protected $with = ['vwItem_details','vwReturned_Item_details'];

    
    public function vwItem_details(){
        return $this->belongsTo(vwWarehouseItems::class,'order_item_id', 'id');
    }
    
    public function vwReturned_Item_details(){
        return $this->belongsTo(vwWarehouseItems::class,'returned_order_item_id', 'id');
    }
}
