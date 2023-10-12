<?php

namespace App\Http\Controllers\Schedules;

use App\Helpers\HIS\Patient;
use Illuminate\Http\Request;
use App\Helpers\HIS\MedsysPatient;
use App\Http\Controllers\Controller;
use App\Helpers\HIS\SysGlobalSetting;
use App\Helpers\HIS\PatientScheduling;
use App\Models\BuildFile\Hospital\Doctor;
use App\Models\Schedules\ORSchedulesModel;
use App\Models\BuildFile\Hospital\Schedules;
use App\Models\BuildFile\Hospital\OperatingRoomCategory;

class ORSchedulePatientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $check_is_allow_medsys;

    public function __construct()
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }


    public function searchPatientData()
    {
        try {
            if ($this->check_is_allow_medsys) {
                $data = (new MedsysPatient())->medsys_inpatient_searchable();
            } else {
                $data = (new Patient())->patient_registry_searchable();
            }
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function searchschedulingPatientData()
    {
        try {
           $data = (new MedsysPatient())->medsys_scheduling_patient_master_searchable();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    
    public function index()
    {
        
        try {
         
            $data = (new PatientScheduling())->medsys_inpatient_searchable();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function show(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function edit(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ORSchedulesModel $oRSchedulesModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Schedules\ORSchedulesModel  $oRSchedulesModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(ORSchedulesModel $oRSchedulesModel)
    {
        //
    }
}
