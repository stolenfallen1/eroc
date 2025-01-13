<?php

namespace App\Models\HIS;

use App\Models\Appointments\AppointmentCenter;
use App\Models\Appointments\AppointmentCenterSectection;
use App\Models\Appointments\PatientAppointmentCheckIn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HIS\PatientAppointmentsTemporary;
use App\Models\HIS\PatientAppointmentTransactions;
use App\Models\HIS\PatientAppointmentPayments;
use App\Models\Profesional\Doctors;

class PatientAppointments extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'CDG_PATIENT_DATA.dbo.PatientAppointments';
    protected $guarded = [];

    public function appointmentsTemporary()
    {
        return $this->hasMany(PatientAppointmentsTemporary::class, 'id', 'temporary_Patient_Id');
    }

    public function appointmentTransactions()
    {
        return $this->hasMany(PatientAppointmentTransactions::class, 'appointment_ReferenceNumber', 'appointment_ReferenceNumber');
    }
    public function appointmentPayments()
    {
        return $this->hasMany(PatientAppointmentPayments::class, 'appointment_ReferenceNumber', 'appointment_ReferenceNumber');
    }
    public function sections()
    {
        return $this->hasMany(AppointmentCenterSectection::class, 'id', 'appointment_section_id');
    }
    public function centers()
    {
        return $this->hasMany(AppointmentCenter::class, 'id', 'appointment_center_id');
    }
    public function doctors()
    {
        return $this->hasMany(Doctors::class, 'id', 'doctor_Id');
    }
    public function patientDetails()
    {
        return $this->belongsTo(PatientMaster::class, 'patient_Id', 'patient_Id');
    }

    public function checkIn()
    {
        return $this->belongsTo(PatientAppointmentCheckIn::class, 'appointment_ReferenceNumber', 'appointment_ReferenceNumber');
    }
    public function getPatientDetailsAttribute()
    {
        // Try fetching from PatientMaster
        $patientDetails = $this->patientDetails()->first();

        // Fallback to PatientAppointmentsTemporary if PatientMaster data is missing
        if ($patientDetails == null) {
            $patientDetails = $this->appointmentsTemporary()->first();
        }

        return $patientDetails;
    }
}
