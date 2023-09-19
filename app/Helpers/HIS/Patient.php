<?php

namespace App\Helpers\HIS;

use Carbon\Carbon;
use App\Models\HIS\PatientMaster;
use App\Models\HIS\PatientRegistry;

class Patient
{
    protected $model_patient_registry;
    protected $model_patient_master;
    protected $user;
    public function __construct()
    {
        $this->model_patient_registry = PatientRegistry::query();
        $this->model_patient_master = PatientMaster::query();
        $this->user = Auth()->user();

    }

    public function patient_registry_searchable()
    {
        $this->patient_registry_searchColumns();
        $this->check_registry_Date();
        $this->filter_by_department();
        $this->model_patient_registry->orderby('id', 'desc');
        $this->model_patient_registry->with('patient_details');
        $per_page = Request()->per_page ?? '';
        return $this->model_patient_registry->paginate($per_page);
    }



    public function patient_registry_searchColumns()
    {
        if(Request()->lastname) {
            $this->model_patient_registry->whereHas('patient_details', function ($query) {
                return $query->where('lastname', 'LIKE', ''.Request()->lastname.'%');
            });
        }
        if(Request()->hospitalno) {
            $this->model_patient_registry->whereHas('patient_details', function ($query) {
                return $query->where('id', 'LIKE', ''.Request()->hospitalno.'%');
            });
        }
        if(Request()->admissionno) {
            $this->model_patient_registry->where('register_id_no', 'LIKE', ''.Request()->admissionno.'%');
        }
    }

    public function filter_by_department()
    {
        
        $check_department = ['isHemodialysis','isPeritoneal','isLINAC','isCOBALT','isChemotherapy','isBrachytherapy','isDebridement','isTBDots','isPAD','isRadioTherapy'];
        foreach ($check_department as $department) {
            if ($department == '1') {
                $this->model_patient_registry->where($department, 1);
            }
        }
    }


    public function check_registry_Date()
    {
        $this->model_patient_registry->whereDate('registry_date', Carbon::now()->format('Y-m-d'));
    }

    public function patient_master_searchable()
    {
        $this->patient_master_searchColumns();
        $per_page = Request()->per_page ?? '1';
        $this->model_patient_master->with('patient_registry_details');
        return $this->model_patient_master->paginate($per_page);
    }

    public function patient_master_searchColumns()
    {
        if(Request()->Firstname) {
            $this->model_patient_master->where('firstname', Request()->Firstname);
        }

        if(Request()->birthdate) {
            $this->model_patient_master->whereDate('birthdate', Request()->birthdate);
        }
        if(Request()->sex) {
            $this->model_patient_master->where('sex_id', Request()->sex);
        }

        if(Request()->Lastname) {
            if (is_numeric(Request()->Lastname)) {
                $this->model_patient_master->where('patient_id', ''.Request()->Lastname.'');
            } else {
                $this->model_patient_master->where('lastname', 'LIKE', ''.Request()->Lastname.'%');
            }
        }
    }
    public function check_patient()
    {
        $this->check_registry_Date();
        $this->check_if_exist_patient();
        return $this->model_patient_registry->select('register_id_no')->first();
    }
    public function check_if_exist_patient()
    {
        $this->model_patient_registry->whereHas('patient_details', function ($query) {
            return $query->where('previous_patient_id', Request()->hospnum);
        });
    }

    public function patient_details()
    {
        return $this->model_patient_master->with('patient_registry_details')->where('previous_patient_id', Request()->hospnum)->first();
    }
}
