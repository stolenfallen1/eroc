<?php

namespace App\Models\BuildFile\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mscHospitalServicesSection extends Model
{
    use HasFactory;
    protected $table = 'mscExamSections';
    protected $connection = "sqlsrv";
    protected $guarded = [];
   
}
