<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class NurseActivity extends Controller
{

public function getChargeList($id) {
    try {
        $accountType = DB::connection('sqlsrv_patient_data')
            ->table('CDG_PATIENT_DATA.dbo.PatientRegistry')
            ->select('guarantor_Name')
            ->where('case_No', $id)
            ->first();

        $cashAssessmentQuery = DB::table('CDG_BILLING.dbo.CashAssessment as ca')
            ->select(
                'ca.patient_Id',
                'ca.case_No',
                'ca.assessnum',
                'ca.revenueID AS revenue_Id',
                'ca.refNum AS referenceNum',
                'ca.itemID AS item_Id',
                'ca.quantity AS Quantity',
                'ca.amount',
                'ca.dosage',
                'ca.recordStatus AS record_Status',
                'ca.requestDescription AS description',
                'ca.ORNumber AS ORN',
                'ca.userId',
                DB::raw('NULL AS requestBy'),
                DB::raw('NULL as process_By'),
                DB::raw('NULL as process_Date'),
                'ca.updatedBy',
                'ca.created_at',
                'ca.updated_at',
                'mscD.description as frequency',
                'fmstc.description as department'
            )
            ->leftJoin('CDG_CORE.dbo.mscDosages as mscD', 'ca.dosage', '=', 'mscD.dosage_id')
            ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'ca.revenueID', '=', 'fmstc.code')
            ->where('ca.case_No', '=', $id)
            ->where('ca.recordStatus', '!=', 'R')
            ->where('ca.quantity', '>=', 1);

        $nurseLogBookQuery = DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
            ->select(
                'cdgLB.patient_Id',
                'cdgLB.case_No',
                DB::raw('NULL AS assessnum'),
                'cdgLB.revenue_Id',
                'cdgLB.requestNum AS referenceNum',
                'cdgLB.item_Id',
                DB::raw('Quantity AS quantity'),
                'cdgLB.amount',
                'cdgLB.dosage',
                'cdgLB.record_Status',
                'cdgLB.description',
                DB::raw('NULL AS ORN'),
                'cdgLB.user_Id as userId',
                'cdgLB.createdby as requestBy',
                'cdgLB.updatedby as updatedBy',
                'cdgLB.createdat',
                'cdgLB.updatedat as updated_at',
                'cdgLB.process_By',
                'cdgLB.process_Date',
                'mscD.description as frequency',
                'fmstc.description as department'
            )
            ->leftJoin('CDG_CORE.dbo.mscDosages as mscD', 'cdgLB.dosage', '=', 'mscD.dosage_id')
            ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'cdgLB.revenue_Id', '=', 'fmstc.code')
            ->where('cdgLB.case_No', '=', $id);

        $dataCharges = $cashAssessmentQuery
            ->unionAll($nurseLogBookQuery)
            ->get();

        if ($dataCharges->isEmpty()) {
            return response()->json([
                'message' => 'No Charges'
            ], 404);
        }

        return response()->json($dataCharges, 200);

    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 500);
    }
}



}
