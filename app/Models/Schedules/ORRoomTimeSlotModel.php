<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ORRoomTimeSlotModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'mscOperatingRoomTimeSlot';
    protected $guarded = [];

    public function scheduleAnesthesia()
    {
        return $this->hasMany(ORScheduleAnesthesiaModel::class, 'schedule_id', 'id');
    }
}
