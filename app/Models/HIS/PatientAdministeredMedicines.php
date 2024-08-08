<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\HIS\services\Patient;
use Illuminate\Database\Eloquent\Model;

class PatientAdministeredMedicines extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientImmunizations';
    protected $guarded = [];
    // protected $with = [''];
}
