<?php

namespace App\Models\Schedules;

use App\Models\Schedules\ORRoomsModel;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORRoomTimeSlotModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORRoomTimSlotTransactionModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomTimeSlots';
    protected $guarded = [];
    protected $with = ['scheduleRoom','scheduleTimeSlot'];
    
    public function scheduleRoom()
    {
        return $this->hasOne(ORRoomsModel::class, 'id', 'room_id');
    }

    public function scheduleTimeSlot()
    {
        return $this->hasOne(ORRoomTimeSlotModel::class, 'id', 'timeslot_id');
    }
}
