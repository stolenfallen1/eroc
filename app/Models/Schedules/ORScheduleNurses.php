<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORCirculatingNursesModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORScheduleNurses extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomSchedule_Nurses';
    protected $guarded = [];
  
    
}
