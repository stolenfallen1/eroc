<?php

namespace App\Models\Schedules;

use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Status;
use App\Models\Schedules\ORScheduleNurses;
use App\Models\Schedules\ORScheduleSurgeonModel;
use App\Models\Schedules\ORScheduleResidentModel;
use App\Models\BuildFile\Hospital\mscHospitalRooms;
use App\Models\Schedules\ORScheduleAnesthesiaModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;

class vwORSchedulesModel extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.vwORSchedules';
}
