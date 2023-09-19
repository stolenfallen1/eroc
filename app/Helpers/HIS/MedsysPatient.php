<?php

namespace App\Helpers\HIS;

use Carbon\Carbon;
use App\Models\HIS\MedsysInpatient;
use App\Models\HIS\MedsysOutpatient;
use App\Models\HIS\MedsysPatientMaster;

class MedsysPatient
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

    // =========================== MEDSYS TABLE ==============================
    public function medsys_patient_searchable()
    {
        $this->currenty_register();
        $this->medsys_patient_searchColumns();
        $this->filter_by_department();
        $this->orderby_adm_date();

        $this->model_medys_outpatient->with('patient_details', 'new_patient_details');
        $per_page = Request()->per_page ?? '';
        return $this->model_medys_outpatient->paginate($per_page);
    }

    public function orderby_adm_date()
    {
       
        // $this->model_medys_outpatient->orderBy('AdmDate', 'desc');
    }

    public function filter_by_department()
    {
        // if($this->department->isHemodialysis == 1) {
        // $this->model_medys_outpatient->where('IsHemodialysis', 1);
        // }
    }

    public function currenty_register()
    {
        $this->model_medys_outpatient->whereDate('AdmDate', ''.Carbon::now()->format('Y-m-d').'');
    }

    public function medsys_patient_searchColumns()
    {

        if(isset(Request()->discharged)) {
            $this->model_medys_outpatient->whereNull('DcrDate');
        }

        if(isset(Request()->admissionno)) {
            $this->model_medys_outpatient->where('IDNum', ''.Request()->admissionno.'');
        }

        if(isset(Request()->hospitalno)) {
            $this->model_medys_outpatient->where('HospNum', ''.Request()->hospitalno.'');
        }
        if(isset(Request()->lastname)) {
            $this->model_medys_outpatient->whereHas('patient_details', function ($query) {

                $patientname = Request()->lastname ?? '';
                $names = explode(',', $patientname); // Split the keyword into firstname and lastname
                $last_name = $names[0];
                $first_name = $names[1]  ?? '';
                if($last_name != '' && $first_name != '') {
                    $query->where('LastName', $last_name);
                    $query->where('FirstName', 'LIKE', ''.ltrim($first_name).'%');
                } else {
                    $query->where('LastName', 'LIKE', ''.Request()->lastname.'%');
                }
            });
        }
    }




    public function medsys_patient_master_searchable()
    {
        $this->medsys_patient_master_searchColumns();
        $this->model_medys_patient_master->with('patient_Inpatient', 'patient_registry');
        $per_page = '-1';
        return $this->model_medys_patient_master->paginate($per_page);
    }


    public function medsys_patient_master_searchColumns()
    {
        $firstname = Request()->Firstname != 'null' ? Request()->Firstname : '';
        $birthdate = Request()->birthdate != 'null' ? Request()->birthdate : '';
        $sex = Request()->sex != 'null' ? Request()->sex : '';
        $lastname = Request()->Lastname != 'null' ? Request()->Lastname : '';

        if($firstname) {
            $this->model_medys_patient_master->where('FirstName', 'LIKE', ''.$firstname.'%');
        }
        if($birthdate) {
            $this->model_medys_patient_master->where('BirthDate', $birthdate);
        }
        if($sex) {
            $this->model_medys_patient_master->where('Sex', $sex);
        }
        if($lastname) {
            if (is_numeric($lastname)) {
                $this->model_medys_patient_master->where('HospNum', $lastname);
            } else {
                $this->model_medys_patient_master->where('LastName', 'LIKE', ''.$lastname.'%');
            }
        }

    }

    public function medsys_check_patient()
    {
        $this->medsys_registry_searchColumns();
        $this->check_registry_Date();
        return $this->model_medys_outpatient->first();
    }

    public function medsys_registry_searchColumns()
    {
        if(isset(Request()->hospnum)) {
            $this->model_medys_outpatient->where('Hospnum', Request()->hospnum);
        }
    }

    public function medsys_patient_details()
    {
        return $this->model_medys_patient_master->where('Hospnum', Request()->hospnum)->first();
    }

    public function check_registry_Date()
    {
        $this->model_medys_outpatient->select('IDNum')->whereDate('AdmDate', Carbon::now()->format('Y-m-d'));
    }

    // CHECK IF PATIENT IS ALREADY CONFINED
    public function medsys_is_confined()
    {
        $this->model_medys_inpatient->where('Hospnum', Request()->hospnum);
        $this->model_medys_inpatient->select('IDNum')->whereNull('DcrDate');
        return $this->model_medys_inpatient->first();
    }

}
