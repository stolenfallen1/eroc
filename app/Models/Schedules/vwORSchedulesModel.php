<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\vwORTimeSlotSchedules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class vwORSchedulesModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.vwORSchedules';
    protected $with = ['timeslot'];
    
    public function timeslot()
    {
        return $this->hasMany(vwORTimeSlotSchedules::class, 'schedule_id', 'id');
    }
}
