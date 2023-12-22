<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Doctor;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORScheduleSurgeonModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomSchedule_Surgeons';
    protected $guarded = [];
    protected $appends = ['surgeon_name'];
    protected $with = ['surgeon_details'];
    public function getSurgeonNameAttribute()
    {
        return $this->lastname . ', ' . $this->firstname . ' ' . $this->middlename;
    }

    public function surgeon_details()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'id')->select('id','lastname','firstname','middlename');
    }
}
