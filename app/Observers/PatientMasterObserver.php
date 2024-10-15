<?php

namespace App\Observers;


use App\Models\HIS\services\Patient;
use App\Models\HIS\MedsysPatientMaster;
use Illuminate\Support\Facades\Log;
use DB;
class PatientMasterObserver
{
    /**
     * Handle the PatientMaster "created" event.
     *
     * @param  \App\Models\PatientMaster  $patientMaster
     * @return void
     */
    public function created(Patient $patientMaster)
    { 
        Log::info('Observer triggered for patient: ' . $patientMaster->patient_Id);
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        try {
            MedsysPatientMaster::whereDate('BirthDate',$patientMaster->birthdate)->updateOrCreate(
                [
                    'HospNum' =>$patientMaster->patient_Id,
                    'LastName' =>$patientMaster->lastname,
                ],
                [
                'HospNum' => $patientMaster->patient_Id,
                'FirstName' => $patientMaster->firstname ?? '',
                'LastName' => $patientMaster->lastname ?? '',
                'MiddleName' => $patientMaster->middlename ?? '',
                'BirthDate' => $patientMaster->birthdate ?? '',
                'Occupation' =>  '',
                'BloodType' =>  '',
            ]);
            // DB::connection('sqlsrv_medsys_patient_data')->commit();
        } catch(\Exception $e) {
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to register outpatient data',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }

    /**
     * Handle the PatientMaster "updated" event.
     *
     * @param  \App\Models\PatientMaster  $patientMaster
     * @return void
     */
    public function updated(Patient $patientMaster)
    {
        //
    }

    /**
     * Handle the PatientMaster "deleted" event.
     *
     * @param  \App\Models\PatientMaster  $patientMaster
     * @return void
     */
    public function deleted(Patient $patientMaster)
    {
        //
    }

    /**
     * Handle the PatientMaster "restored" event.
     *
     * @param  \App\Models\PatientMaster  $patientMaster
     * @return void
     */
    public function restored(Patient $patientMaster)
    {
        //
    }

    /**
     * Handle the PatientMaster "force deleted" event.
     *
     * @param  \App\Models\PatientMaster  $patientMaster
     * @return void
     */
    public function forceDeleted(Patient $patientMaster)
    {
        //
    }
}
