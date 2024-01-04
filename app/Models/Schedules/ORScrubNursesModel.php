<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORCirculatingNursesModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORScrubNursesModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomNurses';
    protected $guarded = [];
    // protected $fillable = ['lastname', 'firstname', 'middlename', 'id','operating_room_scheduled_id'];
    protected $appends = ['scrubnurses'];

  
    public function getScrubNursesAttribute()
    {
        return $this->lastname . ', ' . $this->firstname . ' ' . $this->middlename;
    }
    
}
