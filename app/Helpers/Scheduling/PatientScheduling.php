<?php

namespace App\Helpers\HIS;

use Carbon\Carbon;
use App\Models\HIS\MedsysInpatient;
use App\Models\HIS\MedsysOutpatient;
use App\Models\HIS\MedsysPatientMaster;
class PatientScheduling
{
    protected $model_medys_outpatient;
    protected $model_medys_inpatient;
    protected $model_medys_patient_master;
    public function __construct()
    {
        $this->model_medys_outpatient = MedsysOutpatient::query();
        $this->model_medys_inpatient = MedsysInpatient::query();
        $this->model_medys_patient_master = MedsysPatientMaster::query();
    }
    public function PatientSchedules()
    {
        $this->model_medys_outpatient->with('patient_details', 'new_patient_details');
        $per_page = Request()->per_page ?? '';
        return $this->model_medys_outpatient->paginate($per_page);
    }
}