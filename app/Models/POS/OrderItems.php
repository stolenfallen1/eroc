<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.orderItems';
    protected $guarded = [];

    public function order_items(){
        return $this->hasMany(OrderItems::class,'order_id', 'id');
    }
}
