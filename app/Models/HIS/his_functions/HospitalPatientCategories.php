<?php

namespace App\Models\HIS\his_functions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalPatientCategories extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'mscHospitalPatientCategories';
    protected $guarded = [];
}