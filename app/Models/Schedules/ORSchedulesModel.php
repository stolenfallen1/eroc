<?php

namespace App\Models\Schedules;

use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORPatientModel;
use App\Models\BuildFile\Hospital\Status;
use App\Models\Schedules\ORScheduleNurses;
use App\Models\Schedules\ORScheduleProcedures;
use App\Models\Schedules\ORScheduleSurgeonModel;
use App\Models\Schedules\ORScheduleResidentModel;
use App\Models\BuildFile\Hospital\mscHospitalRooms;
use App\Models\Schedules\ORScheduleAnesthesiaModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;

class ORSchedulesModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomSchedules';
    protected $with = ['scheduleSurgeons', 'scheduleAnesthesia', 'procedures'];
    protected $guarded = [];


    public function patientdetails()
    {
       return $this->belongsTo(MedsysPatientMaster::class, 'patient_id', 'HospNum');
    }

    public function procedures()
    {
        return $this->hasMany(ORScheduleProcedures::class, 'schedule_id', 'id')->whereNull('status');
    }

    public function scheduleSurgeons()
    {
        return $this->hasMany(ORScheduleSurgeonModel::class, 'schedule_id', 'id')->whereNull('status');
    }

    public function scheduleAnesthesia()
    {
        return $this->hasMany(ORScheduleAnesthesiaModel::class, 'schedule_id', 'id')->whereNull('status');
    }

    public function scheduledRoomSlot()
    {
        return $this->hasMany(ORRoomTimSlotTransactionModel::class, 'schedule_id', 'id')->whereNotIn('timeslot_id',[0])->select('schedule_id','timeslot_date','timeslot_id','room_id');
    }

    public function scheduledResident()
    {
        return $this->hasMany(ORScheduleResidentModel::class, 'schedule_id', 'id')->whereNull('status');
    }

    public function scheduledCirculatingNurses()
    {
        return $this->hasMany(ORScheduleNurses::class, 'operating_room_scheduled_id', 'id')->where('specialty_id','1')->whereNull('status');
    }
    
    public function scheduledScrubNurses()
    {
        return $this->hasMany(ORScheduleNurses::class, 'operating_room_scheduled_id', 'id')->where('specialty_id','2')->whereNull('status');
    }
    
     public function scheduledCategory()
    {
        return $this->belongsTo(OperatingRoomCategory::class, 'category_id', 'id');
    }

     public function station_details()
    {
        return $this->belongsTo(mscHospitalRooms::class, 'room_id', 'room_id');
    }

    public function scheduledStatus()
    {
        return $this->belongsTo(Status::class, 'schedule_status_id', 'id');
    }
}
