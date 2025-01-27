<?php

namespace App\Observers;

use App\Models\HIS\services\Patient;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\HIS\MedsysPatientMaster2;
use App\Helpers\HIS\SysGlobalSetting;
use App\Helpers\HIS\PatientMasterObserverHelper;
class HISPatientMasterObserver
{
    /**
     * Handle the Patient "created" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    protected $check_is_allow_medsys;
    protected $patient_master;
    protected $connection;
    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->patient_master = new PatientMasterObserverHelper();
    }
    public function created(Patient $patient){
        try {
            $patientRegistry = $patient->patientRegistry;
            if(!$patientRegistry) {
                throw new \Exception('Patient Registry is empty.');
            }
            $data = json_decode($patientRegistry);
            $services_type = $data[0]->mscAccount_Trans_Types;
            if($this->check_is_allow_medsys && $patient) {
                if(intval($services_type) === 2) {
                    $created_patient_master = MedsysPatientMaster::updateOrCreate(['HospNum' => $patient->patient_Id], $this->patient_master->medsysPatientMasterData($patient));
                    if(!$created_patient_master) {
                        throw new \Exception('Failed to update patient data in Medsys.');
                    }
                }  else {
                    $created_patient_master = MedsysPatientMaster::updateOrCreate(['HospNum' => $patient->patient_Id], $this->patient_master->medsysPatientMasterData($patient));
                    $created_patient_master2 = MedsysPatientMaster2::updateOrCreate(['HospNum' => $patient->patient_Id], $this->patient_master->medsysPatientMaster2Data($patient));
                    if(!$created_patient_master || !$created_patient_master2) {
                        throw new \Exception('Failed to update patient data in Medsys.');
                    }
                }
            } else {
                throw new \Exception('Permission denied or Patient empty.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to update patient into Medsys: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Patient "updated" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    public function updated(Patient $patient)
    {
        try {
            $patientRegistry = $patient->patientRegistry;
            if(!$patientRegistry) {
                throw new \Exception('Patient Registry is empty.');
            }
            $data = json_decode($patientRegistry);
            $services_type = $data[0]->mscAccount_Trans_Types;
            if($this->check_is_allow_medsys && $patient) {
                $patientInfo    = MedsysPatientMaster::findOrFail($patient->patient_Id);
                $patientInfo2   = MedsysPatientMaster2::findOrFail($patient->patient_Id);
                if(!$patientInfo || !$patientInfo2) {
                    throw new \Exception('Patient data not found.');
                }
                if(intval($services_type) === 2) {
                    $updated_patient_master = MedsysPatientMaster::where('HospNum', $patient->patient_Id)->update($this->patient_master->medsysPatientMasterData($patient));
                    if(!$updated_patient_master) {
                        throw new \Exception('Failed to update patient data in Medsys.');
                    }
                }  else {
                    $updated_patient_master  = MedsysPatientMaster::where('HospNum', $patient->patient_Id)->update($this->patient_master->medsysPatientMasterData($patient));
                    $updated_patient_master2 = MedsysPatientMaster2::where('HospNum', $patient->patient_Id)->update($this->patient_master->medsysPatientMaster2Data($patient));
                    if(!$updated_patient_master || !$updated_patient_master2) {
                        throw new \Exception('Failed to update patient data in Medsys.');
                    }
                }
            } else {
                throw new \Exception('Permission denied or Patient empty.');
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to update patient into Medsys: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Patient "deleted" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    public function deleted(Patient $patient)
    {
        //
    }

    /**
     * Handle the Patient "restored" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    public function restored(Patient $patient)
    {
        //
    }

    /**
     * Handle the Patient "force deleted" event.
     *
     * @param  \App\Models\HIS\services\Patient  $patient
     * @return void
     */
    public function forceDeleted(Patient $patient)
    {
        //
    }
}
