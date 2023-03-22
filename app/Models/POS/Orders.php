<?php

namespace App\Models\POS;

use App\Models\POS\OrderItems;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Orders extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CDG_POS.dbo.orders';
    protected $guarded = [];

    public function order_items(){
        return $this->hasMany(OrderItems::class,'order_id', 'id');
    }

    
}
