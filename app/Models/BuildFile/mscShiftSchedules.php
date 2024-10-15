<?php

namespace App\Models\BuildFile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mscShiftSchedules extends Model
{
    use HasFactory;
    protected $table = 'mscShiftSchedules';
    protected $connection = "sqlsrv";
}
