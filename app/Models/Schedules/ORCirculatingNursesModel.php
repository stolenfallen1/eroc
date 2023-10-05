<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ORCirculatingNursesModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomNurses';
    protected $fillable = ['lastname', 'firstname', 'middlename', 'id','operating_room_scheduled_id'];
    protected $appends = ['circulatingnurses'];



    public function getCirculatingNursesAttribute()
    {
        return $this->lastname . ', ' . $this->firstname . ' ' . $this->middlename;
    }
}
