<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MscRefundType extends Model
{
    use HasFactory;   
    protected $table = 'CDG_CORE.dbo.mscPosRefundType';
    protected $connection = "sqlsrv";
}
