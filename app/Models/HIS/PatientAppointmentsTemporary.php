<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientAppointments;

class PatientAppointmentsTemporary extends Model
{
    use HasFactory;
    
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientAppointmentsTemporary';
    protected $guarded = [];

    protected function appointments() {
        return $this->belongsTo(PatientAppointments::class, 'temporary_Patient_Id', 'id');
    }
}
