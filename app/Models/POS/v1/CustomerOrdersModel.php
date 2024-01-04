<?php

namespace App\Models\POS\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrdersModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'orderItems_temp';
    protected $guarded = [];
}
