<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class NurseActivity extends Controller
{
    //
    // public function getChargeList($id) {
    //     try {
    //         $accountType = DB::connection('sqlsrv_patient_data')->table('CDG_PATIENT_DATA.dbo.PatientRegistry')->select('guarantor_Name')->where('case_No', $id)->first();
    //         if($accountType->guarantor_Name === 'Self Pay') {
    //             $dataCharges = DB::table('CDG_BILLING.dbo.CashAssessment as ca')
    //             ->select(
    //                 'ca.patient_Id',
    //                 'ca.case_No',
    //                 'ca.assessnum',
    //                 'ca.revenueID',
    //                 'ca.recordStatus',
    //                 'ca.refNum',
    //                 'ca.itemID',
    //                 'ca.quantity',
    //                 'ca.requestDescription',
    //                 'ca.ORNumber',
    //                 'ca.userId',
    //                 'ca.created_at',
    //                 'ca.updated_at',
    //                 'ca.updatedBy',
    //                 'fmstc.description as department',
    //                 DB::raw('MIN(nLB.record_Status) as logbookStatus'),
    //                 DB::raw('MIN(nLB.process_Date) lbProcessDate ')
    //             )
    //             ->distinct()
    //             ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'ca.revenueID', '=', 'fmstc.code')
    //             ->leftJoin('CDG_PATIENT_DATA.dbo.NurseLogBook as nLB', 'ca.revenueID', '=', 'nLB.revenue_Id')
    //             ->where('ca.case_No', '=', $id)
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
    //                 'updatedBy',
    //                 'ca.updated_at',
    //                 'fmstc.description'
    //             )
    //             ->get();

    //         } else {
    //             $dataCharges = DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
    //                 ->select(
    //                     'cdgLB.patient_Id',
    //                     'cdgLB.case_No',
    //                     'cdgLB.revenue_Id',
    //                     'cdgLB.requestNum',
    //                     'cdgLB.referenceNum',
    //                     'cdgLB.item_Id',
    //                     'cdgLB.Quantity',
    //                     'cdgLB.description',
    //                     'cdgLB.record_Status',
    //                     'cdgLB.process_By',
    //                     'cdgLB.process_Date',
    //                     'cdgLB.createdat',
    //                     'cdgLb.updatedat',
    //                     'fmstc.description as department',
    //                 )
    //                 ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'cdgLB.revenue_id', '=', 'fmstc.code')
    //                 ->get();
    //         }

    //         $charges = $dataCharges->map(function($item) {
    //             return [
    //                 'patient_id'        => $item->patient_Id,
    //                 'case_No'             => $item->case_No,
    //                 'revenue_Id'         => $item->revenue_Id                    ?? $item->revenueID,
    //                 'LBRecordStatus'     => $item->logbookStatus                 ?? $item->record_Status,
    //                 'CARecordStatus'     => $item->recordStatus                  ?? $item->record_Status,
    //                 'item_Id'            => $item->item_Id                       ?? $item->itemID,
    //                 'description'        => (isset($item->requestDescription) 
    //                                      ? $item->requestDescription 
    //                                      : $item->description                    ?? null),
    //                 'department'         => $item->department                    ?? null,
    //                 'Quantity'           => $item->Quantity                      ?? $item->quantity,
    //                 'referenceNum'       => $item->referenceNum                  ?? $item->refNum,
    //                 'assessnum'          => $item->assessnum                     ?? null,
    //                 'ORN'                => $item->ORNumber                     ?? null,
    //                 'requestBy'          => $item->userId                       ?? null,
    //                 'processedBy'        => $item->updatedBy                    ?? null,
    //                 'cashCharged_at'     => $item->created_at                   ?? null,
    //                 'cashProcessed_at'   => $item->updated_at                   ?? null, 
    //                 'hmoCharged_at'      => $item->createdat                    ?? null,
    //                 'hmoProcessed_at'    => $item->process_Date                 ?? null,
    //             ];
                
    //         });

    //         if ($dataCharges->isEmpty()) {
    //             return response()->json([
    //                 'message' => 'No Charges'
    //             ], 404);
    //         }
   
    //         return response()->json($charges, 200);

    //     } catch (\Exception $e) {

    //         return response()->json(['message' => $e->getMessage()], 500);
    //     }
    // }

    // public function getChargeList($id) {
    //     try {
    //         $accountType = DB::connection('sqlsrv_patient_data')
    //             ->table('CDG_PATIENT_DATA.dbo.PatientRegistry')
    //             ->select('guarantor_Name')
    //             ->where('case_No', $id)
    //             ->first();
    
    //         if ($accountType->guarantor_Name === 'Self Pay') {
    //             $dataCharges = DB::table('CDG_BILLING.dbo.CashAssessment as ca')
    //                 ->select(
    //                     'ca.patient_Id',
    //                     'ca.case_No',
    //                     'ca.assessnum',
    //                     'ca.revenueID',
    //                     'ca.recordStatus',
    //                     'ca.refNum',
    //                     'ca.itemID',
    //                     'ca.quantity',
    //                     'ca.requestDescription',
    //                     'ca.ORNumber',
    //                     'ca.userId',
    //                     'ca.created_at',
    //                     'ca.updated_at',
    //                     'ca.updatedBy',
    //                     'fmstc.description as department',
    //                     DB::raw('MIN(nLB.record_Status) as logbookStatus'),
    //                     DB::raw('MIN(nLB.process_Date) as lbProcessDate')
    //                 )
    //                 ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'ca.revenueID', '=', 'fmstc.code')
    //                 ->leftJoin('CDG_PATIENT_DATA.dbo.NurseLogBook as nLB', 'ca.revenueID', '=', 'nLB.revenue_Id')
    //                 ->where('ca.case_No', '=', $id)
    //                 ->where('ca.recordStatus', '!=', 'R')
    //                 ->where('ca.quantity', '>=', 1)
    //                 ->groupBy(
    //                     'ca.patient_Id',
    //                     'ca.case_No',
    //                     'ca.assessnum',
    //                     'ca.revenueID',
    //                     'ca.recordStatus',
    //                     'ca.itemID',
    //                     'ca.quantity',
    //                     'ca.requestDescription',
    //                     'ca.refNum',
    //                     'ca.ORNumber',
    //                     'ca.userId',
    //                     'ca.created_at',
    //                     'ca.updatedBy',
    //                     'ca.updated_at',
    //                     'fmstc.description'
    //                 )
    //                 ->get();
    
    //         } else {
    //             $dataCharges = DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
    //                 ->select(
    //                     'cdgLB.patient_Id',
    //                     'cdgLB.case_No',
    //                     'cdgLB.revenue_Id',
    //                     'cdgLB.requestNum',
    //                     'cdgLB.referenceNum',
    //                     'cdgLB.item_Id',
    //                     'cdgLB.Quantity',
    //                     'cdgLB.description',
    //                     'cdgLB.record_Status',
    //                     'cdgLB.process_By',
    //                     'cdgLB.createdby as userId',
    //                     'cdgLB.process_Date',
    //                     'cdgLB.createdat',
    //                     'cdgLB.updatedat', 
    //                     'fmstc.description as department'
    //                 )
    //                 ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'cdgLB.revenue_id', '=', 'fmstc.code')
    //                 ->where('cdgLB.case_No', '=', $id) 
    //                 ->where('cdgLB.Quantity', '>=', 1) 
    //                 ->get();
    //         }
    
    //         $charges = $dataCharges->map(function($item) {
    //             return [
    //                 'patient_id'        => $item->patient_Id,
    //                 'case_No'           => $item->case_No,
    //                 'revenue_Id'        => $item->revenue_Id ?? $item->revenueID,
    //                 'LBRecordStatus'    => $item->logbookStatus ?? $item->record_Status,
    //                 'CARecordStatus'    => $item->recordStatus ?? $item->record_Status,
    //                 'item_Id'           => $item->item_Id ?? $item->itemID,
    //                 'description'       => isset($item->requestDescription) 
    //                                          ? $item->requestDescription 
    //                                          : ($item->description ?? null),
    //                 'department'        => $item->department ?? null,
    //                 'Quantity'          => $item->Quantity ?? $item->quantity,
    //                 'referenceNum'      => $item->referenceNum ?? $item->refNum,
    //                 'assessnum'         => $item->assessnum ?? null,
    //                 'ORN'               => $item->ORNumber ?? null,
    //                 'requestBy'         => $item->userId ?? null,
    //                 'processedBy'       => $item->updatedBy ?? null,
    //                 'cashCharged_at'    => $item->created_at ?? null,
    //                 'cashProcessed_at'  => $item->updated_at ?? null, 
    //                 'hmoCharged_at'     => $item->createdat ?? null,
    //                 'hmoProcessed_at'   => $item->process_Date ?? null,
    //             ];
    //         });
    
    //         if ($dataCharges->isEmpty()) {
    //             return response()->json([
    //                 'message' => 'No Charges'
    //             ], 404);
    //         }
    
    //         return response()->json($charges, 200);
    
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => $e->getMessage()], 500);
    //     }
    // }

    public function getChargeList($id) {
        try {
            // Retrieve account type
            $accountType = DB::connection('sqlsrv_patient_data')
                ->table('CDG_PATIENT_DATA.dbo.PatientRegistry')
                ->select('guarantor_Name')
                ->where('case_No', $id)
                ->first();

            if (!$accountType) {
                return response()->json(['message' => 'Account not found'], 404);
            }

            // Query for charges based on account type
            $query = ($accountType->guarantor_Name === 'Self Pay') 
                ? $this->getCashAssessmentQuery($id)
                : $this->getNurseLogBookQuery($id);

            $dataCharges = $query->get();

            if ($dataCharges->isEmpty()) {
                return response()->json(['message' => 'No Charges'], 404);
            }

            // Transform data
            // $charges = $dataCharges->map(fn($item) => $this->transformChargeData($item));
            $charges = $dataCharges->map(function($item) {
                return $this->transformChargeData($item);
            });
            

            return response()->json($charges, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

/**
 * Build query for Cash Assessment charges
 */
    private function getCashAssessmentQuery($id) {
        return DB::table('CDG_BILLING.dbo.CashAssessment as ca')
            ->select(
                'ca.patient_Id',
                'ca.case_No',
                'ca.assessnum',
                'ca.revenueID as revenue_Id',
                'ca.recordStatus as CARecordStatus',
                'ca.refNum as referenceNum',
                'ca.itemID as item_Id',
                'ca.quantity as Quantity',
                'ca.requestDescription as description',
                'ca.ORNumber as ORN',
                'ca.userId as requestBy',
                'ca.created_at as cashCharged_at',
                'ca.updated_at as cashProcessed_at',
                'ca.updatedBy as processedBy',
                'fmstc.description as department',
                DB::raw('MIN(nLB.record_Status) as LBRecordStatus'),
                DB::raw('MIN(nLB.process_Date) as hmoProcessed_at')
            )
            ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'ca.revenueID', '=', 'fmstc.code')
            ->leftJoin('CDG_PATIENT_DATA.dbo.NurseLogBook as nLB', 'ca.revenueID', '=', 'nLB.revenue_Id')
            ->where('ca.case_No', $id)
            ->where('ca.recordStatus', '!=', 'R')
            ->where('ca.quantity', '>=', 1)
            ->groupBy(
                'ca.patient_Id',
                'ca.case_No',
                'ca.assessnum',
                'ca.revenueID',
                'ca.recordStatus',
                'ca.itemID',
                'ca.quantity',
                'ca.requestDescription',
                'ca.refNum',
                'ca.ORNumber',
                'ca.userId',
                'ca.created_at',
                'ca.updated_at',
                'ca.updatedBy',
                'fmstc.description'
            );
    }

    /**
     * Build query for Nurse Log Book charges
     */
    private function getNurseLogBookQuery($id){
        return DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
            ->select(
                'cdgLB.patient_Id',
                'cdgLB.case_No',
                'cdgLB.revenue_Id',
                'cdgLB.requestNum as referenceNum',
                'cdgLB.item_Id',
                'cdgLB.Quantity',
                'cdgLB.description',
                'cdgLB.record_Status as LBRecordStatus',
                'cdgLB.process_By as processedBy',
                'cdgLB.createdby as requestBy',
                'cdgLB.process_Date as hmoProcessed_at',
                'cdgLB.createdat as hmoCharged_at',
                'cdgLB.updatedat as cashProcessed_at',
                'fmstc.description as department'
            )
            ->leftJoin('CDG_CORE.dbo.fmsTransactionCodes as fmstc', 'cdgLB.revenue_id', '=', 'fmstc.code')
            ->where('cdgLB.case_No', $id)
            ->where('cdgLB.Quantity', '>=', 1);
    }

    /**
     * Transform charge data for output
     */
    private function transformChargeData($item)
    {
        return [
            'patient_id'        => $item->patient_Id,
            'case_No'           => $item->case_No,
            'revenue_Id'        => $item->revenue_Id,
            'LBRecordStatus'    => $item->LBRecordStatus ?? null,
            'CARecordStatus'    => $item->CARecordStatus ?? null,
            'item_Id'           => $item->item_Id,
            'description'       => $item->description ?? '-',
            'department'        => $item->department ?? null,
            'Quantity'          => $item->Quantity ?? '-',
            'referenceNum'      => $item->referenceNum ?? null,
            'ORN'               => $item->ORN ?? null,
            'requestBy'         => $item->requestBy ?? null,
            'processedBy'       => $item->processedBy ?? null,
            'cashCharged_at'    => $item->cashCharged_at ?? null,
            'cashProcessed_at'  => $item->cashProcessed_at ?? null,
            'hmoCharged_at'     => $item->hmoCharged_at ?? null,
            'hmoProcessed_at'   => $item->hmoProcessed_at ?? null,
        ];
    }
}
