<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientAppointments;

class PatientAppointmentTransactions extends Model
{
    use HasFactory;
    
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientAppointmentTransactions';
    protected $guarded = [];

    public function appointments() {
        return $this->belongsTo(PatientAppointments::class, 'appointment_ReferenceNumber', 'appointment_ReferenceNumber');
    }
}
