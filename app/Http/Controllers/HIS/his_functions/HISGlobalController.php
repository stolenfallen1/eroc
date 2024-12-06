<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\HIS\his_functions\ViewIncomeReport;
use Illuminate\Http\Request;
use PDF;

class HISGlobalController extends Controller
{
    //
    public function getRevenueDescription($revenueID) 
    {
        try {
            $revenueDescription = TransactionCodes::where('code', $revenueID)
                ->where('isActive', 1)
                ->value('description');

            return $revenueDescription;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function printDailyIncomeReport(Request $request) 
    {
        try {
            $day = $request->query('date');
            $patient_Type = $request->query('patient_Type');
            $revenueID = $request->query('revenueID');
            if ($patient_Type) {
                $patient_Type = $patient_Type == 1 ? 'O' : ($patient_Type == 2 ? 'E' : 'I');
                $patient_Identifier = $patient_Type == 'O' ? 'Outpatient' : ($patient_Type == 'E' ? 'Emergency' : 'Inpatient');
            }

            $transactions = ViewIncomeReport::whereNotNull('case_No')
                ->where('patient_Type', $patient_Type)
                ->where('revenueID', $revenueID)
                ->whereDate('transaction_date', $day)
                ->get();

            $totalPostedCount = $transactions->filter(function ($transaction) {
                return $transaction->quantity > 0 && $transaction->amount > 0;
            })->count();

            $totalReturnedCount = $transactions->filter(function ($transaction) {
                return $transaction->quantity < 0 && $transaction->amount < 0;
            })->count();

            $revenueDescription = $this->getRevenueDescription($revenueID);
            
            $pdf_data = [
                'transactions' => $transactions,
                'totalPostedCount' => $totalPostedCount,
                'totalReturnedCount' => $totalReturnedCount,
                'title' => $revenueDescription,
                'sub_title' => 'Daily Income Report',
                'report_identifier' => $patient_Identifier,
                'currency' => '₱',
                'printed_By' => Auth()->user()->idnumber,
            ];

            $pdf = PDF::loadView('his.report.ancillary.income_report', ['pdf_data' => $pdf_data])->setPaper('letter', 'potrait');
            $pdf->render();    
            return $pdf->stream('Income Report' . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function printMonthlyIncomeReport(Request $request) 
    {
        try {
            $month = $request->query('month');
            $patient_Type = $request->query('patient_Type');
            $revenueID = $request->query('revenueID');
            if ($patient_Type) {
                $patient_Type = $patient_Type == 1 ? 'O' : ($patient_Type == 2 ? 'E' : 'I');
                $patient_Identifier = $patient_Type == 'O' ? 'Outpatient' : ($patient_Type == 'E' ? 'Emergency' : 'Inpatient');
            } 

            $transactions = ViewIncomeReport::whereNotNull('case_No')
                ->where('patient_Type', $patient_Type)
                ->where('revenueID', $revenueID)
                ->whereRaw("FORMAT(transaction_date, 'yyyy-MM') = ?", [$month]) 
                ->get();

            $totalPostedCount = $transactions->filter(function ($transaction) {
                return $transaction->quantity > 0 && $transaction->amount > 0;
            })->count();

            $totalReturnedCount = $transactions->filter(function ($transaction) {
                return $transaction->quantity < 0 && $transaction->amount < 0;
            })->count();

            $revenueDescription = $this->getRevenueDescription($revenueID);
            
            $pdf_data = [
                'transactions' => $transactions,
                'totalPostedCount' => $totalPostedCount,
                'totalReturnedCount' => $totalReturnedCount,
                'title' => $revenueDescription,
                'sub_title' => 'Monthly Income Report',
                'report_identifier' => $patient_Identifier,
                'currency' => '₱',
                'printed_By' => Auth()->user()->idnumber,
            ];

            $pdf = PDF::loadView('his.report.ancillary.income_report', ['pdf_data' => $pdf_data])->setPaper('letter', 'potrait');
            $pdf->render();    
            return $pdf->stream('Income Report' . '.pdf');
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
