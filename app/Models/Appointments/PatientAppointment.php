<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\Appointments\PatientAppointmentPayment;
use App\Models\Appointments\PatientAppointmentTransaction;
use App\Models\Appointments\AppointmentCenterSectection;
use App\Models\BuildFile\Hospital\Doctor;
class PatientAppointment extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'PatientAppointments';
    protected $guarded = [];
    protected $with = ['doctor','transactions','transactions.item','payments','center','status','section'];
   
    public function patient(){
        return $this->belongsTo(PatientAppointmentsTemporary::class,'temporary_Patient_Id','id');
    }

    public function center(){
        return $this->belongsTo(AppointmentCenter::class,'appointment_center_id','id');
    }

    public function section(){
        return $this->belongsTo(AppointmentCenterSectection::class,'appointment_section_id','id');
    }

    public function doctor(){
        return $this->hasOne(Doctor::class,'id','doctor_Id');
    }

    public function payments(){
        return $this->belongsTo(PatientAppointmentPayment::class,'appointment_ReferenceNumber','appointment_ReferenceNumber');
    }
   
    public function transactions(){
        return $this->hasMany(PatientAppointmentTransaction::class,'appointment_ReferenceNumber','appointment_ReferenceNumber');
    }

    public function status(){
        return $this->belongsTo(AppointmentStatus::class,'status_Id','id');
    }
}
