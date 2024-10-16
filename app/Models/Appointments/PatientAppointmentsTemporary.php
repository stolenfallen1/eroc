<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
class PatientAppointmentsTemporary extends  Authenticatable
{
    use HasFactory,HasApiTokens;
    protected $connection = 'sqlsrv_patient_data';
    protected $table = 'PatientAppointmentsTemporary';
    protected $guarded = [];
    protected $appends = ['name'];
    protected $hidden = [
        'portal_UID',
        'api_token',
        'token',
        'portal_PWD',
    ];
    
    public function getNameAttribute()
    {
        return ucwords($this->lastname).', '.ucwords($this->firstname);
    }

    public function createToken()
    {
        $token = sha1(time());
        $this->token = $token;
        $this->save();
        return $token;
    }

    public function revokeToken()
    {
        $this->token = null;
        $this->save();
    }
    
}
