<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORDoctorSpecialtyModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORDoctor extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomDoctor';
    protected $appends = ['doctor_name'];
    protected $guarded = [];
    protected $with = ['specialty'];

    public function getDoctorNameAttribute()
    {
        return $this->lastname . ', ' .$this->firstname. ' ' .$this->middlename;
    }

     public function specialty()
    {
        return $this->belongsTo(ORDoctorSpecialtyModel::class, 'specialty_id', 'id');
    }
}
