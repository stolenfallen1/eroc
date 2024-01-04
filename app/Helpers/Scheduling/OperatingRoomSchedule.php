<?php

namespace App\Helpers\Scheduling;

use Carbon\Carbon;
use App\Models\Schedules\ORSchedulesModel;
use App\Models\Schedules\vwORSchedulesModel;

class OperatingRoomSchedule
{
    protected $or_schedules;
    protected $vw_or_schedules;
    public function __construct()
    {
        $this->or_schedules = ORSchedulesModel::query();
        $this->vw_or_schedules = vwORSchedulesModel::query();

    }

    public function QueueScheduled()
    {
        $status = ['15','20','21','22','23','24'];
        $category = ['1','2','3'];
        $date = Request()->date ?? Carbon::now()->format('Y-m-d');
        $this->or_schedules->whereIn('schedule_status_id', $status)->whereIn('category_id', $category)->whereDate('schedule_date', $date);
        $this->or_schedules->with('patientdetails', 'patientdetails.opd_registry', 'patientdetails.patient_Inpatient', 'scheduledRoomSlot', 'scheduledCategory', 'station_details', 'scheduledStatus', 'scheduledResident', 'scheduledCirculatingNurses', 'scheduledScrubNurses');
        $per_page = Request()->per_page ?? '';
        return $this->or_schedules->paginate($per_page);
    }

    public function QueueScheduledOptha()
    {
        $status = ['15','20','21','22','23','24'];
        $category = ['4'];
        $this->or_schedules->whereIn('schedule_status_id', $status)->whereIn('category_id', $category)->whereDate('schedule_date', Carbon::now()->format('Y-m-d'));
        $this->or_schedules->with('patientdetails', 'patientdetails.opd_registry', 'patientdetails.patient_Inpatient', 'scheduledRoomSlot', 'scheduledCategory', 'station_details', 'scheduledStatus', 'scheduledResident', 'scheduledCirculatingNurses', 'scheduledScrubNurses');
        $per_page = Request()->per_page ?? '';
        return $this->or_schedules->paginate($per_page);
    }

    public function confirmed_scheduled()
    {
        $today = Request()->date ?? Carbon::now()->format('Y-m-d');
        $status = ['14','15','16','17','18','19'];
        $this->searchPatientName();
        $this->or_schedules->whereIn('schedule_status_id', $status)->whereBetween('schedule_date', [$today, $today])->get();
        $this->or_schedules->with('patientdetails', 'patientdetails.opd_registry', 'patientdetails.patient_Inpatient', 'scheduledRoomSlot', 'scheduledCategory', 'scheduledResident', 'scheduledCirculatingNurses', 'scheduledScrubNurses', 'station_details', 'scheduledStatus');
        $per_page = Request()->per_page ?? '';
        return $this->or_schedules->paginate($per_page);
    }

    public function operatingroom_status()
    {
        $today = Request()->date ?? Carbon::now()->format('Y-m-d');
        $status = ['20','21','22','23','24','25','26'];
        $this->or_schedules->whereIn('schedule_status_id', $status)->whereBetween('schedule_date', [$today, $today])->get();
        $this->or_schedules->with('patientdetails', 'patientdetails.opd_registry', 'patientdetails.patient_Inpatient', 'scheduledRoomSlot', 'scheduledCategory', 'scheduledResident', 'scheduledCirculatingNurses', 'scheduledScrubNurses', 'station_details', 'scheduledStatus');
        $this->searchPatientName();
        $per_page = Request()->per_page ?? '';
        return $this->or_schedules->paginate($per_page);
    }

    public function searchPatientName()
    {

        if (isset(Request()->keyword)) {
            $this->or_schedules->whereHas('patientdetails', function ($query) {
                $patientname = Request()->keyword ?? '';
                $names = explode(',', $patientname); // Split the keyword into firstname and lastname
                $last_name = $names[0];
                $first_name = $names[1]  ?? '';
                if ($last_name != '' && $first_name != '') {
                    $query->where('LastName', $last_name);
                    $query->where('FirstName', 'LIKE', '' . ltrim($first_name) . '%');
                } else {
                    $query->where('LastName', 'LIKE', '' . Request()->keyword . '%');
                }
            });
        }
    }
    public function pending_scheduled()
    {
        $today = Request()->date ?? Carbon::now()->addDay()->format('Y-m-d');
        $this->searchPatientName();
        $this->or_schedules->whereDate('schedule_date', $today);
        $this->or_schedules->with('patientdetails', 'patientdetails.opd_registry', 'patientdetails.patient_Inpatient', 'scheduledRoomSlot', 'scheduledCategory', 'scheduledResident', 'scheduledCirculatingNurses', 'scheduledScrubNurses', 'station_details', 'scheduledStatus');
        $per_page = Request()->per_page ?? '';
        return $this->or_schedules->paginate($per_page);
    }

    // for mobile portal function
    public function vwORSchedules()
    {
        $today = Carbon::now()->format('Y-m-d');
        $this->vwSearchPatientName();
        // $this->checkStatus();
        $this->checkcategory();
        $this->vw_or_schedules->whereBetween('schedule_date', [$today, $today]);
        $per_page = Request()->per_page ?? '';
        return $this->vw_or_schedules->paginate($per_page);
    }

    public function vwORPatientDetails()
    {
        $today = Carbon::now()->format('Y-m-d');
        $this->vw_or_schedules->whereBetween('schedule_date', [$today, $today]);
        if (isset(Request()->id)) {
            $this->vw_or_schedules->where('id', Request()->id);
        }
        return $this->vw_or_schedules->first();
    }

    public function vwSearchPatientName()
    {
        if (isset(Request()->keyword)) {
            $patientname = Request()->keyword ?? '';
            $this->vw_or_schedules->where('patientname', 'LIKE', '' . $patientname . '%');
        }
    }
    public function checkcategory()
    {
        if (isset(Request()->orTab)) {
            $this->vw_or_schedules->where('category_id', Request()->orTab);
        }
    }
    public function checkStatus()
    {
        $status = ['14','15','16','17','18','19','20','21','22','23','24','25','26'];
        if(Auth()->user()->role['name'] == 'Scrub Nurse') {
            $status = ['20','21','22','23','24','25','26'];
        } elseif(Auth()->user()->role['name'] == 'Reception') {
            $status = ['14','15','16','17','18','19','20'];
        }
        if (isset(Request()->status)) {
            $this->vw_or_schedules->where('schedule_status_id', Request()->status);
        } else {
            $this->vw_or_schedules->whereIn('schedule_status_id', $status);
        }
    }
}
