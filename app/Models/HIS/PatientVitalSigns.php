<?php

namespace App\Models\HIS;

use App\Models\HIS\services\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientVitalSigns extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientVitalSigns';
    protected $guarded = [];
}
