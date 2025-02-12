<?php

namespace App\Models\POS;

use App\Models\User;
use App\Models\POS\Payments;
use App\Models\POS\Customers;
use App\Models\POS\OrderItems;
use App\Models\POS\vwCustomers;
use Illuminate\Database\Eloquent\Model;
use App\Models\MMIS\inventory\ItemBatch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Controllers\POS\ClosingTransactionController;

class Orders extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'orders';
    protected $guarded = [];
    protected $with = ['order_items.ItemBatch','order_items.vwItem_details','customers','users'];

    public function order_items(){
        return $this->hasMany(OrderItems::class,'order_id', 'id')->where('isDeleted',0);
    }
    public function return_order_items(){
        return $this->hasMany(OrderItems::class,'order_id', 'id')->where('isDeleted',0);
    }
    public function users()
    {
        return $this->belongsTo(User::class, 'pa_userid', 'idnumber');
    }
    public function customers(){
        return $this->belongsTo(vwCustomers::class,'customer_id', 'id');
    }

    public function payment(){
        return $this->hasOne(Payments::class,'order_id', 'id');
    }

    public function search(){
      
    }
}
