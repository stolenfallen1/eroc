<?php

namespace App\Models\Schedules;

use App\Models\BuildFile\Hospital\Sex;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\ORNursePositionModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORCirculatingNursesModel extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomNurses';
    // protected $fillable = ['lastname', 'firstname', 'middlename', 'id','operating_room_scheduled_id'];
    protected $appends = ['circulatingnurses'];
    protected $with = ['sex','position'];
    protected $guarded = [];

    public function getCirculatingNursesAttribute()
    {
        return $this->lastname . ', ' . $this->firstname . ' ' . $this->middlename;
    }
    

    public function sex()
    {
        return $this->belongsTo(Sex::class, 'gendercode', 'id');
    }

    public function position()
    {
        return $this->belongsTo(ORNursePositionModel::class, 'position_id', 'id');
    }
}
