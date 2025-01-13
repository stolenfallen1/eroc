<?php

namespace App\Models\Appointments;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAppointments extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'PatientPortalUsers';
    protected $guarded = [];
 
    public $timestamps = false;
    public function getNameAttribute()
    {
        return $this->lastname.', '.$this->firstname.' '.$this->middlename;
    }

    public function patient(){
        return $this->hasOne(PatientAppointmentsTemporary::class,'user_id','id');
    }

    public function center(){
        return $this->belongsTo(AppointmentCenter::class,'center_id','id');
    }

    public function section(){
        return $this->belongsTo(AppointmentCenterSectection::class,'section_id','id');
    }

    public function createToken()
    {
        $token = sha1(time());
        $this->api_token = $token;
        $this->save();
        return $token;
    }

    public function revokeToken()
    {
        $this->api_token = null;
        $this->save();
    }
}
