<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Doctor;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORScheduleAnesthesiaModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomSchedule_Anesthesia';
    protected $guarded = [];
    protected $appends = ['anesthesia_name'];

    protected $with = ['anesthesia_details'];
    public function getAnesthesiaNameAttribute()
    {
        return $this->lastname . ', ' . $this->firstname . ' ' . $this->middlename;
    }
    public function anesthesia_details()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'id')->select('id','lastname','firstname','middlename');
    }
}
