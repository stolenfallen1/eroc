<?php

namespace App\Models\BuildFile\FMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevenueClass extends Model
{
    use HasFactory;
    protected $table = 'fmsRevenueClasses';
    protected $connection = "sqlsrv";
    protected $guarded = [];

}
