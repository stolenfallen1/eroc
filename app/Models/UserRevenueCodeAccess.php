<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRevenueCodeAccess extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'sysRevenueCode';
    protected $guarded = [];
    public $timestamps = false;
}
