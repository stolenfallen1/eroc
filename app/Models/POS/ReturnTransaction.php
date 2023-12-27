<?php

namespace App\Models\POS;

use App\Models\POS\Orders;
use App\Models\POS\Customers;
use App\Models\POS\OrderItems;
use App\Models\POS\vwCustomers;
use App\Models\POS\vwReturnDetails;
use Illuminate\Database\Eloquent\Model;
use App\Models\POS\ReturnDetailsTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnTransaction extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'refunds';
    protected $guarded = [];
  
    protected $with = ['orders.order_items','orders.customers','orders.payment','return_orders.return_order_items'];

    public function refund_items(){
        return $this->hasMany(ReturnDetailsTransaction::class,'refund_id', 'id');
    }
   
    public function orders(){
        return $this->belongsTo(Orders::class,'order_id', 'id');
    }

    public function return_orders(){
        return $this->belongsTo(Orders::class,'returned_order_id', 'id');
    }
}
