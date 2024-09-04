<?php

namespace App\Models\HIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientAppointmentsTemporary;
use App\Models\HIS\PatientAppointmentTransactions;
use App\Models\HIS\PatientAppointmentPayments;

class PatientAppointments extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientAppointments';
    protected $guarded = [];

    public function appointmentsTemporary() {
        return $this->belongsTo(PatientAppointmentsTemporary::class, 'id', 'temporary_Patient_id');
    }

    public function appointmentTransactions() {
        return $this->belongsTo(PatientAppointmentTransactions::class, 'appointment_ReferenceNumber', 'appointment_ReferenceNumber');
    }
    public function appointmentPayments() {
        return $this->belongsTo(PatientAppointmentPayments::class, 'appointment_ReferenceNumber', 'appointment_ReferenceNumber');
    }
}
