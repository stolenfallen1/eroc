<?php

namespace App\Observers;

use App\Models\HIS\services\PatientRegistry;
use App\Models\HIS\MedsysOutpatient;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
class MedsysOutpatientObserver
{
    /**
     * Handle the PatientRegistry "created" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function created(PatientRegistry $patientRegistry)
    {
        Log::info('Observer triggered for patient: ' . $patientRegistry->patient_Id);
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        try {
            MedsysOutpatient::updateOrCreate(
            [
                'HospNum'   => $patientRegistry->patient_Id,
                'IDNum'   => $patientRegistry->case_No.'B',
            ],
            [
                'HospNum'   => $patientRegistry->patient_Id,
                'IDNum'   => $patientRegistry->case_No.'B',
                'AdmDate'   => Carbon::now(),
            ]);
            DB::connection('sqlsrv_medsys_patient_data')->commit();
        } catch(\Exception $e) {
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to register outpatient data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle the PatientRegistry "updated" event.
     *
     * @param  \App\Models\HIS\services\PatientRegistry  $patientRegistry
     * @return void
     */
    public function updated(PatientRegistry $patientRegistry)
    {
        //
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
