<?php

namespace App\Helpers\HIS;

use Carbon\Carbon;
use App\Models\BuildFile\GlobalSetting;

class SysGlobalSetting
{
    protected $model;
    public function __construct()
    {
        $this->model = GlobalSetting::query();
    }


    public function check_is_allow_medsys_status()
    {
        $result = $this->model->where('setting_code','MedsysRegistration')->first();
        // return $result->value;
        if($result->value == 'True'){
            return true;
        }
        return false;
    }

    public function check_is_allow_laboratory_auto_rendering() 
    {
        $result = $this->model->where('setting_code', 'HISLaboratoryAutoRenderMechanism')->first();
        if ($result->value == 'True') {
            return true;
        }
        return false;
    }

}
