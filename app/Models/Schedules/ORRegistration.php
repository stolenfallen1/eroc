<?php

namespace App\Models\Schedules;

use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Database\Eloquent\Model;
use App\Models\BuildFile\Hospital\Status;
use App\Models\Schedules\ORRegistrationTimeSlot;
use App\Models\Schedules\ORScheduleSurgeonModel;
use App\Models\Schedules\ORRegistrationProcedures;
use App\Models\Schedules\ORScheduleAnesthesiaModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Schedules\ORRoomTimSlotTransactionModel;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;
use App\Models\Schedules\ORRegistrationPreferredSurgeon;
use App\Models\Schedules\ORRegistrationPreferredAnesthesia;

class ORRegistration extends Model
{
    use HasFactory;
    protected $connection = 'sqlsrv_schedules';
    protected $table = 'CDG_SCHEDULES.dbo.OperatingRoomRegistration';
    protected $guarded = [];
    protected $with = ['procedures', 'timeslots','scheduledStatus','surgeon','anesthesia'];

    public function procedures()
    {
       return $this->hasMany(ORRegistrationProcedures::class, 'patient_id', 'patient_id');
    }
    public function timeslots()
    {
       return $this->hasMany(ORRegistrationTimeSlot::class, 'patient_id', 'patient_id');
    }
     public function surgeon()
    {
       return $this->hasMany(ORRegistrationPreferredSurgeon::class, 'patient_id', 'patient_id');
    }
     public function anesthesia()
    {
       return $this->hasMany(ORRegistrationPreferredAnesthesia::class, 'patient_id', 'patient_id');
    }
    public function scheduledStatus()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }
}
