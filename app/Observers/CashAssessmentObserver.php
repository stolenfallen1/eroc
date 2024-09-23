<?php

namespace App\Observers;

use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\MedsysCashAssessment;
use DB;
class CashAssessmentObserver
{
    /**
     * Handle the CashAssessment "created" event.
     *
     * @param  \App\Models\HIS\his_functions\CashAssessment  $cashAssessment
     * @return void
     */
    public function created(CashAssessment $cashAssessment)
    {
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        try {
            
            MedsysCashAssessment::create([
                'HospNum'   => $cashAssessment->patient_Id,
                'IdNum'   => $cashAssessment->case_No.'B',
                'Name'   => $cashAssessment->patient_Name,
                'TransDate' => $cashAssessment->transdate,
                'AssessNum' => $cashAssessment->assessnum,
                'Indicator' => $cashAssessment->indicator,
                'DrCr' => $cashAssessment->drcr,
                'ItemID' => $cashAssessment->itemID,
                'Quantity' => $cashAssessment->quantity,
                'RefNum' => $cashAssessment->refNum,
                'Amount' => $cashAssessment->amount,
                'UserID' => $cashAssessment->userId,
                'RevenueID' => $cashAssessment->revenueID,
                'RequestDocID' => $cashAssessment->requestDoctorID,
            ]);
            DB::connection('sqlsrv_medsys_billing')->commit();
        } catch(\Exception $e) {
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            return response()->json([
                'message' => 'Failed to register outpatient data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle the CashAssessment "updated" event.
     *
     * @param  \App\Models\HIS\his_functions\CashAssessment  $cashAssessment
     * @return void
     */
    public function updated(CashAssessment $cashAssessment)
    {
        //
    }

    /**
     * Handle the CashAssessment "deleted" event.
     *
     * @param  \App\Models\HIS\his_functions\CashAssessment  $cashAssessment
     * @return void
     */
    public function deleted(CashAssessment $cashAssessment)
    {
        //
    }

    /**
     * Handle the CashAssessment "restored" event.
     *
     * @param  \App\Models\HIS\his_functions\CashAssessment  $cashAssessment
     * @return void
     */
    public function restored(CashAssessment $cashAssessment)
    {
        //
    }

    /**
     * Handle the CashAssessment "force deleted" event.
     *
     * @param  \App\Models\HIS\his_functions\CashAssessment  $cashAssessment
     * @return void
     */
    public function forceDeleted(CashAssessment $cashAssessment)
    {
        //
    }
}
