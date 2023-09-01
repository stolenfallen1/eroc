<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundType extends Model
{
    use HasFactory;
    protected $table = 'mscPosRefundType';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
