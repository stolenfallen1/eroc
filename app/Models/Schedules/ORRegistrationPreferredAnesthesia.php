<?php

namespace App\Models\Schedules;

use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Model;
use App\Models\Schedules\OperatingRoomProcedures;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;

class ORRegistrationPreferredAnesthesia extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomRegistration_Anesthesia';
    protected $guarded = [];
    protected $with = ['details'];

    public function details()
    {
       return $this->belongsTo(ORDoctor::class, 'anesthesia_id', 'id');
    }
}
