<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalPlan extends Model
{
    use HasFactory;
    protected $table = 'mscHospitalPlan';
    protected $connection = "sqlsrv";
    protected $guarded = [];
}
