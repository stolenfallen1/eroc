<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class NurseActivity extends Controller
{
//     public function getChargeList($id) {
//         try {
//             // Retrieve account type
//             $accountType = DB::connection('sqlsrv_patient_data')
//                 ->table('CDG_PATIENT_DATA.dbo.PatientRegistry')
//                 ->select('guarantor_Name')
//                 ->where('case_No', $id)
//                 ->first();

//             if (!$accountType) {
//                 return response()->json(['message' => 'Account not found'], 404);
//             }

//             // Query for charges based on account type
//             $query = ($accountType->guarantor_Name === 'Self Pay') 
//                 ? $this->getCashAssessmentQuery($id)
//                 : $this->getNurseLogBookQuery($id);
                
//             $dataCharges = $query->get();

//             if ($dataCharges->isEmpty()) {
//                 return response()->json(['message' => 'No Charges'], 404);
//             }

//             // Transform data
//             // $charges = $dataCharges->map(fn($item) => $this->transformChargeData($item));
//             $charges = $dataCharges->map(function($item) {
//                 return $this->transformChargeData($item);
//             });
            

//             return response()->json($charges, 200);
//         } catch (\Exception $e) {
//             return response()->json(['message' => $e->getMessage()], 500);
//         }
//     }

// /**
//  * Build query for Cash Assessment charges
//  */
//     private function getCashAssessmentQuery($id) {
//         return DB::table('CDG_BILLING.dbo.CashAssessment as ca')
//             ->select(
//                 'ca.patient_Id',
//                 'ca.case_No',
//                 'ca.assessnum',
//                 'ca.revenueID as revenue_Id',
//                 'ca.recordStatus as CARecordStatus',
//                 'ca.refNum as referenceNum',
//                 'ca.itemID as item_Id',
//                 'ca.quantity as Quantity',
//                 'ca.requestDescription as description',
//                 'ca.ORNumber as ORN',
//                 'ca.userId as requestBy',
//                 'ca.created_at as cashCharged_at',
//                 'ca.updated_at as cashProcessed_at',
//                 'ca.updatedBy as processedBy',
//                 'fmstc.description as department',
//                 DB::raw('MIN(nLB.record_Status) as LBRecordStatus'),
//                 DB::raw('MIN(nLB.process_Date) as hmoProcessed_at')
//             )
//             ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'ca.revenueID', '=', 'fmstc.code')
//             ->leftJoin('CDG_PATIENT_DATA.dbo.NurseLogBook as nLB', 'ca.revenueID', '=', 'nLB.revenue_Id')
//             ->where('ca.case_No', $id)
//             ->where('ca.recordStatus', '!=', 'R')
//             ->where('ca.quantity', '>=', 1)
//             ->groupBy(
//                 'ca.patient_Id',
//                 'ca.case_No',
//                 'ca.assessnum',
//                 'ca.revenueID',
//                 'ca.recordStatus',
//                 'ca.itemID',
//                 'ca.quantity',
//                 'ca.requestDescription',
//                 'ca.refNum',
//                 'ca.ORNumber',
//                 'ca.userId',
//                 'ca.created_at',
//                 'ca.updated_at',
//                 'ca.updatedBy',
//                 'fmstc.description'
//             );
//     }

//     /**
//      * Build query for Nurse Log Book charges
//      */
//     private function getNurseLogBookQuery($id){
//         return DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
//             ->select(
//                 'cdgLB.patient_Id',
//                 'cdgLB.case_No',
//                 'cdgLB.revenue_Id',
//                 'cdgLB.requestNum as referenceNum',
//                 'cdgLB.item_Id',
//                 'cdgLB.Quantity',
//                 'cdgLB.description',
//                 'cdgLB.record_Status as LBRecordStatus',
//                 'cdgLB.process_By as processedBy',
//                 'cdgLB.createdby as requestBy',
//                 'cdgLB.process_Date as hmoProcessed_at',
//                 'cdgLB.createdat as hmoCharged_at',
//                 'cdgLB.updatedat as cashProcessed_at',
//                 'fmstc.description as department'
//             )
//             ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'cdgLB.revenue_id', '=', 'fmstc.code')
//             ->where('cdgLB.case_No', $id)
//             ->where('cdgLB.Quantity', '>=', 1);
//     }

//     /**
//      * Transform charge data for output
//      */
//     private function transformChargeData($item)
//     {
//         return [
//             'patient_id'        => $item->patient_Id,
//             'case_No'           => $item->case_No,
//             'revenue_Id'        => $item->revenue_Id,
//             'LBRecordStatus'    => $item->LBRecordStatus ?? null,
//             'CARecordStatus'    => $item->CARecordStatus ?? null,
//             'item_Id'           => $item->item_Id,
//             'description'       => $item->description ?? '-',
//             'department'        => $item->department ?? null,
//             'Quantity'          => $item->Quantity ?? '-',
//             'referenceNum'      => $item->referenceNum ?? null,
//             'ORN'               => $item->ORN ?? null,
//             'requestBy'         => $item->requestBy ?? null,
//             'processedBy'       => $item->processedBy ?? null,
//             'cashCharged_at'    => $item->cashCharged_at ?? null,
//             'cashProcessed_at'  => $item->cashProcessed_at ?? null,
//             'hmoCharged_at'     => $item->hmoCharged_at ?? null,
//             'hmoProcessed_at'   => $item->hmoProcessed_at ?? null,
//         ];
//     }

// public function getChargeList($id) {
//     try {
//         $accountType = DB::connection('sqlsrv_patient_data')
//             ->table('CDG_PATIENT_DATA.dbo.PatientRegistry')
//             ->select('guarantor_Name')
//             ->where('case_No', $id)
//             ->first();

//         $cashAssessmentQuery = DB::table('CDG_BILLING.dbo.CashAssessment as ca')
//             ->select(
//                 'ca.patient_Id',
//                 'ca.case_No',
//                 'ca.assessnum',
//                 'ca.revenueID AS revenue_Id',
//                 'ca.refNum AS referenceNum',
//                 'ca.itemID AS item_Id',
//                 'ca.quantity AS Quantity',
//                 'ca.amount',
//                 'ca.dosage',
//                 'ca.recordStatus AS record_Status',
//                 'ca.requestDescription AS description',
//                 'ca.ORNumber AS ORN',
//                 'ca.userId',
//                 'ca.created_at',
//                 'ca.updated_at',
//                 'ca.updatedBy',
//                 'mscD.description as frequency',
//                 'fmstc.description as department'
//             )
//             ->leftJoin('CDG_CORE.dbo.mscDosages as mscD', 'ca.dosage', '=', 'mscD.dosage_id')
//             ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'ca.revenueID', '=', 'fmstc.code') // Use 'ca.revenueID'
//             ->where('ca.case_No', '=', $id)
//             ->where('ca.recordStatus', '!=', 'R')
//             ->where('ca.quantity', '>=', 1);

//         $nurseLogBookQuery = DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
//             ->select(
//                 'cdgLB.patient_Id',
//                 'cdgLB.case_No',
//                 DB::raw('NULL AS assessnum'), 
//                 'cdgLB.revenue_Id',
//                 'cdgLB.requestNum AS referenceNum',
//                 'cdgLB.item_Id',
//                 DB::raw('Quantity AS quantity'),
//                 'cdgLB.amount',
//                 'cdgLB.dosage',
//                 'cdgLB.record_Status',
//                 'cdgLB.description',
//                 DB::raw('NULL AS ORN'),
//                 'cdgLB.user_Id as userId',
//                 'cdgLB.createdby as requestBy',
//                 'cdgLB.updatedby as updatedBy',
//                 'cdgLB.createdat as created_at',
//                 'cdgLB.updatedat as updated_at',
//                 'mscD.description as frequency',
//                 'fmstc.description as department'
//             )
//             ->leftJoin('CDG_CORE.dbo.mscDosages as mscD', 'cdgLB.dosage', '=', 'mscD.dosage_id')
//             ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'cdgLB.revenue_Id', '=', 'fmstc.code') 
//             ->where('cdgLB.case_No', '=', $id);

//         $dataCharges = $cashAssessmentQuery
//             ->unionAll($nurseLogBookQuery)
//             ->get();

//         if ($dataCharges->isEmpty()) {
//             return response()->json([
//                 'message' => 'No Charges'
//             ], 404);
//         }

//         return response()->json($dataCharges, 200);

//     } catch (\Exception $e) {
//         return response()->json(['message' => $e->getMessage()], 500);
//     }
// }

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
