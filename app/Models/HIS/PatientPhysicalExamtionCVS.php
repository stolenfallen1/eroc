<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPhysicalExamtionCVS extends Model
{
    use HasFactory;
    protected $connections = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientPhysicalExamtionCVS';
    protected $guarded = [];
}
