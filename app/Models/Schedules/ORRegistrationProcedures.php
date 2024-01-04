<?php

namespace App\Models\Schedules;

use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\OperatingRoomProcedures;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;

class ORRegistrationProcedures extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomRegistration_Procedure';
    protected $guarded = [];
    protected $with = ['details'];

    public function details()
    {
       return $this->belongsTo(OperatingRoomProcedures::class, 'procedure_id', 'id');
    }
}
