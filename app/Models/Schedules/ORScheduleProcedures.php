<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\OperatingRoomProcedures;
use App\Models\Schedules\ORCirculatingNursesModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ORScheduleProcedures extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomSchedule_Procedure';
    protected $guarded = [];
    protected $with = ['procedure_details'];
     public function procedure_details()
    {
        return $this->belongsTo(OperatingRoomProcedures::class, 'procedure_id', 'id');
    }
}
