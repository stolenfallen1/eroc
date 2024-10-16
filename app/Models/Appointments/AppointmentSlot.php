<?php

namespace App\Models\Appointments;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Appointments\AppointmentType;
use App\Models\Appointments\AppointmentCenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppointmentSlot extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv';
    protected $table = 'AppointmentSlots';
    protected $guarded = [];

    protected $with = ['center','type'];
    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return 'Slot '.$this->no.' - Period '.$this->period;
    }
    public function center(){
        return $this->belongsTo(AppointmentCenter::class,'center_id','id');
    }

    public function type(){
        return $this->belongsTo(AppointmentType::class,'appointment_type','id');
    }
}
