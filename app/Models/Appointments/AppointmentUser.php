<?php

namespace App\Models\Appointments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\Appointments\AppointmentCenter;
use App\Models\Appointments\AppointmentCenterSectection;
class AppointmentUser extends  Authenticatable
{
    use HasFactory,HasApiTokens;
    protected $connection = 'sqlsrv';
    protected $table = 'AppointmentUsers';
    protected $guarded = [];
    protected $appends = ['name'];
    protected $with = ['patient','center','section'];
    protected $hidden = [
        'api_token',
    ];
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
