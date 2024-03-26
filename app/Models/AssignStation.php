<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\mscHospitalStation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssignStation extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'sysUserStation';
    protected $guarded = [];
    public $timestamps = false;
   
}
