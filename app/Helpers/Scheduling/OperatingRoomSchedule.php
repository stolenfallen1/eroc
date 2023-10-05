<?php

namespace App\Helpers\Scheduling;

use Carbon\Carbon;
use App\Models\HIS\MedsysInpatient;
use App\Models\HIS\MedsysOutpatient;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\Schedules\ORSchedulesModel;

class OperatingRoomSchedule
{
    protected $or_schedules;
    public function __construct()
    {
        $this->or_schedules = ORSchedulesModel::query();
    }

    public function confirmed_scheduled()
    {
        $this->or_schedules->whereDate('schedule_date', Carbon::now()->format('Y-m-d'));
        $this->or_schedules->with('patientdetails','scheduledRoomSlot','scheduledCategory','station_details');
        $per_page = Request()->per_page ?? '';
        return $this->or_schedules->paginate($per_page);
    }


    public function pending_scheduled()
    {
        $this->or_schedules->whereDate('schedule_date', '>',Carbon::now()->format('Y-m-d'));
        $this->or_schedules->with('patientdetails','scheduledRoomSlot','scheduledCategory','station_details');
        $per_page = Request()->per_page ?? '';
        return $this->or_schedules->paginate($per_page);
    }
}
