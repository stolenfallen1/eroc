<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Doctor;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use DB;

class ORScheduleResidentModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomSchedule_ResidentDoctors';
    protected $guarded = [];
    protected $with = ['resident_details'];
    public function resident_details()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'id')->select('id','lastname','firstname','middlename');
    }
}
