<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vwCustomerOrders extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'vwCustomerOrders';
    protected $guarded = [];
}
