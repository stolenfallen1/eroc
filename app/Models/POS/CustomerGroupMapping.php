<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroupMapping extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_pos';
    protected $table = 'CustomerGroupMapping';
    protected $guarded = [];
}
