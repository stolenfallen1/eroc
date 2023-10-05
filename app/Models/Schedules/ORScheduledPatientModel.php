<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORScheduleSurgeonModel;
use App\Models\Schedules\ORScheduleAnesthesiaModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORSchedulesModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomSchedules';
    protected $with = ['scheduleSurgeons', 'scheduleAnesthesia'];
    protected $guarded = [];

    public function scheduleSurgeons()
    {
        return $this->hasMany(ORScheduleSurgeonModel::class, 'schedule_id', 'id');
    }
    public function scheduleAnesthesia()
    {
        return $this->hasMany(ORScheduleAnesthesiaModel::class, 'schedule_id', 'id');
    }
}
