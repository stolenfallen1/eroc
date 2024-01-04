<?php

namespace App\Models\Schedules;

use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORRoomTimeSlotModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Schedules\ORRoomTimSlotTransactionModel;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;

class ORRegistrationTimeSlot extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomRegistration_TimeSlots';
    protected $guarded = [];
   
    protected $with = ['scheduledRoomSlot'];
    public function scheduledRoomSlot()
    {
        return $this->hasOne(ORRoomTimeSlotModel::class, 'id', 'timeslot_id');
    }
}
