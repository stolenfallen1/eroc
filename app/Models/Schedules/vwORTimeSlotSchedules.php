<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class vwORTimeSlotSchedules extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.vwORTimeSlotSchedules';
    
  
}
