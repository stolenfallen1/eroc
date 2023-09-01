<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftSchedules extends Model
{
    use HasFactory;
    protected $table = 'mscShiftSchedules';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
