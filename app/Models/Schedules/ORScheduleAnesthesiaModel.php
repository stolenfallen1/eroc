<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ORScheduleAnesthesiaModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomSchedule_Anesthesia';
    protected $guarded = [];
    protected $appends = ['anesthesia_name'];

    public function getAnesthesiaNameAttribute()
    {
        return $this->lastname . ', ' . $this->firstname . ' ' . $this->middlename;
    }
}
