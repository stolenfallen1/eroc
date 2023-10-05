<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORPatientModel;
use App\Models\Schedules\ORScheduleNurses;
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
    protected $with = ['scheduleSurgeons', 'scheduleAnesthesia'];
    protected $guarded = [];


    public function patientdetails()
    {
       return $this->belongsTo(ORPatientModel::class, 'patient_id', 'HospNum');
    }


    public function scheduleSurgeons()
    {
        return $this->hasOne(ORScheduleSurgeonModel::class, 'schedule_id', 'id');
    }

    public function scheduleAnesthesia()
    {
        return $this->hasOne(ORScheduleAnesthesiaModel::class, 'schedule_id', 'id');
    }

    public function scheduledRoomSlot()
    {
        return $this->hasOne(ORRoomTimSlotTransactionModel::class, 'schedule_id', 'id');
    }

    public function scheduledResident()
    {
        return $this->hasOne(ORScheduleResidentModel::class, 'schedule_id', 'id');
    }

    public function scheduledCirculatingNurses()
    {
        return $this->hasOne(ORScheduleNurses::class, 'operating_room_scheduled_id', 'id');
    }
    
    public function scheduledScrubNurses()
    {
        return $this->hasMany(ORScheduleNurses::class, 'operating_room_scheduled_id', 'id');
    }
    
     public function scheduledCategory()
    {
        return $this->belongsTo(OperatingRoomCategory::class, 'category_id', 'id');
    }

     public function station_details()
    {
        return $this->belongsTo(mscHospitalRooms::class, 'room_id', 'room_id');
    }
}
