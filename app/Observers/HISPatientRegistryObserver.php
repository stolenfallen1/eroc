<?php

namespace App\Observers;

use App\Helpers\HIS\PatientRegistryObserverHelper;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\HIS\services\PatientRegistry;
use Illuminate\Support\Facades\Log;
use App\Helpers\HIS\PatientMasterObserverHelper;
use Carbon\Carbon;
use App\Models\HIS\MedsysERMaster;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\HIS\MedsysOutpatient;
use App\Models\HIS\medsys\TbPatient;
class HISPatientRegistryObserver
{
    /**
     * Handle the PatientRegistry "created" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    protected $check_is_allow_medsys;
    protected $medsysObserverHelper;
    protected $check_patient_account_type;
    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->medsysObserverHelper = new PatientRegistryObserverHelper();
        $this->check_patient_account_type = new PatientMasterObserverHelper();
    }
    public function created(PatientRegistry $patientRegistry)
    {
        try{
            if($this->check_is_allow_medsys && $patientRegistry) {
                if(intval($patientRegistry->mscAccount_Trans_Types) === 5) {
                    $this->medsysObserverHelper->processRequestToSaveDataInPatientMaster($patientRegistry);
                    $this->medsysObserverHelper->processRequestToSaveDataInTbERMaster($patientRegistry); 
                    $this->medsysObserverHelper->processRequestToSaveDataInTbOutPatient($patientRegistry);
                } elseif(intval($patientRegistry->mscAccount_Trans_Types) === 2) {
                    $this->medsysObserverHelper->processRequestToSaveDataInTbOutPatient($patientRegistry);
                } else {
                    $this->medsysObserverHelper->processRequestToSaveDataInTbPatient($patientRegistry);
                }
            } else {
                Log::error('Permission denied or Patient Registry is invalid.');
                throw new \Exception('Permission denied or Patient Registry is invalid.');
            }
        } catch(\Exception $e) {
            Log::error('Failed to process patient data in Medsys: ' . $e->getMessage());
            throw new \Exception('Failed to insert patient into Medsys: ' . $e->getMessage());
        } 
    }

    /**
     * Handle the PatientRegistry "updated" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function updated(PatientRegistry $patientRegistry) {
        try {
            if($this->check_is_allow_medsys && $patientRegistry) {
                if(intval($patientRegistry->mscAccount_Trans_Types) === 5) {
                    $this->medsysObserverHelper->processRequestToSaveDataInPatientMaster($patientRegistry);
                    $this->medsysObserverHelper->processRequestToSaveDataInTbERMaster($patientRegistry);
                    $this->medsysObserverHelper->processRequestToSaveDataInTbOutPatient($patientRegistry);
                } elseif(intval($patientRegistry->mscAccount_Trans_Types) === 2) {
                    $this->medsysObserverHelper->processRequestToSaveDataInTbOutPatient($patientRegistry);
                } else {
                    $this->medsysObserverHelper->processRequestToSaveDataInTbPatient($patientRegistry);
                }
            } else {
                throw new \Exception('Permission denied or Patient Registry is invalid.');
            }
        } catch(\Exception $e) {
            Log::error('Failed to update patient info in  Medsys: ' . $e->getMessage());
            throw new \Exception('Failed to update patient into Medsys: ' . $e->getMessage());
        }
    }

    /**
     * Handle the PatientRegistry "deleted" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function deleted(PatientRegistry $patientRegistry)
    {
        //
    }

    /**
     * Handle the PatientRegistry "restored" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function restored(PatientRegistry $patientRegistry)
    {
        //
    }

    /**
     * Handle the PatientRegistry "force deleted" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function forceDeleted(PatientRegistry $patientRegistry)
    {
        //
    }
}
