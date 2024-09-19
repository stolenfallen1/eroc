<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\SOA\OutPatient;
class SOAController extends Controller
{
    //  

    public function createStatmentOfAccount($id) {
        $caseNo = intval($id);
        $data = OutPatient::with('patientBillingInfo')->where('case_No', $caseNo)->take(1)->get();
        // return $data;
        $patientInfo = $data->map(function($item) {
            return [
                'Patient_Name'      => $item->lastname . ', ' . $item->firstname . ' ' . $item->middlename . ' ' . $item->suffix_description,
                'Patient_Address'   => isset($item->address) ? $item->address : 'N/A',
                'Account_No'        => $item->patient_Id,
                'Guarantor'         => $item->guarantor_Name,
                'Credit_Limit'      => $item->guarantor_Credit_Limit,
                'Admission_No'      => $item->case_No,
                'Hospital_No'       => $item->branch_Id,
                'Hospital_Name'     => $item->patient_Id,
                'Time_Admitted'     => isset($item->registry_Date) ? date('h:i A', strtotime($item->registry_Date)) : '',
                'Billed_Date'       => isset($item->build_Date) ? date('Y/m/d', strtotime($item->build_Date)) : '',
                'Billed_Time'       => isset($item->build_Date) ? date('h:i A', strtotime($item->build_Date)) : '',
            ];
        });

        $firstRow = true;
        $runningBalance = 0; 
        $totalCharges = 0;
        
        $patientBill = $data->flatMap(function($item) use (&$firstRow, &$runningBalance) {
            return $item->patientBillingInfo->map(function($billing) use (&$firstRow, &$runningBalance) {
                $charges = floatval(str_replace(',', '', ($billing->amount * intval($billing->quantity))));  
        
                if ($firstRow && ($billing->drcr === 'D' || $billing->drcr === 'P')) {
                    $runningBalance = $charges;
                    $firstRow = false;
                } elseif($firstRow && $billing->drcr === 'C') {
                    $runningBalance = 0;
                    $firstRow = false;
                } else {
                    if ($billing->drcr === 'D' || $billing->drcr === 'P') {
                        $runningBalance += $charges;
                    } elseif ($billing->drcr === 'C') {
                        $runningBalance -= $charges;
                    }
                }
        
                return [
                    'Date' => date('Y/m/d', strtotime($billing->transDate)),
                    'Reference_No' => $billing->revenueID . ' ' . $billing->refNum,
                    'Description' => $billing->exam_description,
                    'Quantity' => isset($billing->quantity) ? intval($billing->quantity) : 1,
                    'Charges' => isset($billing->drcr) && ($billing->drcr === 'C' || intval($billing->quantity) <= 0)
                                ? number_format(0, 2)
                                : number_format($charges, 2),
                    'Credit' => isset($billing->drcr) && ($billing->drcr === 'C' || intval($billing->quantity) <= 0) 
                                ? number_format($charges, 2) 
                                : number_format(0, 2),
                    'Balance' => number_format($runningBalance, 2), 
                ];
            });
        });

        $account_statement = [
            'Patient_Info'  => $patientInfo->toArray(),
            'Patient_Bill'  => $patientBill->toArray(),
            'Total_Charges' => number_format($totalCharges = $runningBalance, 2),
            'Run_Date'      => Carbon::now()->format('Y/m/d'),
            'Run_Time'      => Carbon::now()->format('g:i A')
        ];

        $filename   = 'Statement_of_account';
        $html       = view('his.report.soa.statement_of_account', $account_statement)->render();
        $pdf        = PDF::loadHTML($html)->setPaper('letter', 'portrait');

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font   = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
        $dompdf->get_canvas()->page_text(510, 23, "{PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0, 0, 0));

        $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d g:i A');
        $dompdf->get_canvas()->page_text(35, 750, $currentDateTime, $font, 10, array(0, 0, 0));
        return $pdf->stream($filename . '.pdf');
    }

    public function createStatmentOfAccountSummary($id) {
        $data = OutPatient::with(['patientBillingInfo' => function($query) {
            $query->orderBy('revenueID', 'asc'); 
        }])
        ->where('case_No', $id)
        ->take(1)
        ->get();

        $patientInfo = $data->map(function($item) {
            return [
                'Patient_Name'      => $item->lastname . ', ' . $item->firstname . ' ' . $item->middlename . ' ' . $item->suffix_description,
                'Patient_Address'   => isset($item->address) ? $item->address : 'N/A',
                'Account_No'        => $item->patient_Id,
                'Guarantor'         => $item->guarantor_Name,
                'Credit_Limit'      => $item->guarantor_Credit_Limit,
                'Admission_No'      => $item->case_No,
                'Hospital_No'       => $item->branch_Id,
                'Hospital_Name'     => $item->patient_Id,
                'Date_Admitted'     => isset($item->registry_Date) ? date('Y/m/d', strtotime($item->registry_Date)) : '',
                'Time_Admitted'     => isset($item->registry_Date) ? date('h:i A', strtotime($item->registry_Date)) : '',
                'Billed_Date'       => isset($item->build_Date) ? date('Y/m/d', strtotime($item->build_Date)) : '',
                'Billed_Time'       => isset($item->build_Date) ? date('h:i A', strtotime($item->build_Date)) : '',
            ];
        });
        
        $billsPayment = DB::connection('sqlsrv_billingOut')->select('EXEC sp_billing_SOACompleteSummarized ?', [$id]);
        $totalChargesSummary = collect($billsPayment)
            ->groupBy('RevenueID') 
            ->map(function($groupedItems) {
                $totalAmount = $groupedItems->sum(function($billing) {
                    return floatval(str_replace(',', '', $billing->Charges ?? 0));
                });
                $revenueDescription = $groupedItems->first()->Description ?? 'N/A';
                $accountType = $groupedItems->first()->DrCr ?? 'N/A';
                $RevenueID = $groupedItems->first()->RevenueID ?? '';
                $Credit = $groupedItems->first()->Credit_MD ? floatval($groupedItems->first()->Credit) : number_format(0, 2);
                $Discount = $groupedItems->first()->Discount ? floatval($groupedItems->first()->Discount) : number_format(0, 2);
                $PhicMD = $groupedItems->first()->PHIC_MD ? floatval($groupedItems->first()->PHIC_MD) : number_format(0, 2);
                $PaymentType = $groupedItems->first()->PaymentType ? floatval($groupedItems->first()->PaymentType) : number_format(0, 2);

                return [
                    'Total'         => $totalAmount,
                    'Description'   => $revenueDescription,
                    'AccountType'   => $accountType,
                    'RevenueID'     => $RevenueID,
                    'Credit'        => $Credit,
                    'Discount'      => $Discount,
                    'PHIC'          => $PhicMD,
                    'Payment'       => $PaymentType
                ];
            });


            $firstRow = true;
            $runningBalance = 0; 
            $totalCharges = 0;
            
            $patientBill = $data->flatMap(function($item) use (&$firstRow, &$runningBalance) {
                return $item->patientBillingInfo->map(function($billing) use (&$firstRow, &$runningBalance) {
                    $charges = floatval(str_replace(',', '', ($billing->amount * intval($billing->quantity))));  
            
                    if ($firstRow && ($billing->drcr === 'D' || $billing->drcr === 'P')) {
                        $runningBalance = $charges;
                        $firstRow = false;
                    } elseif($firstRow && $billing->drcr === 'C') {
                        $runningBalance = 0;
                        $firstRow = false;
                    } else {
                        if ($billing->drcr === 'D' || $billing->drcr === 'P') {
                            $runningBalance += $charges;
                        } elseif ($billing->drcr === 'C') {
                            $runningBalance -= $charges;
                        }
                    }
            
                    return [
                        'Date' => date('Y/m/d', strtotime($billing->transDate)),
                        'Reference_No' => $billing->revenueID . ' ' . $billing->refNum,
                        'Description' => $billing->exam_description,
                        'Quantity' => isset($billing->quantity) ? intval($billing->quantity) : 1,
                        'Charges' => isset($billing->drcr) && ($billing->drcr === 'C' || intval($billing->quantity) <= 0)
                                    ? number_format(0, 2)
                                    : number_format($charges, 2),
                        'Credit' => isset($billing->drcr) && ($billing->drcr === 'C' || intval($billing->quantity) <= 0) 
                                    ? number_format($charges, 2) 
                                    : number_format(0, 2),
                        'Balance' => number_format($runningBalance, 2), 
                    ];
                });
            });
   
        $patientBillInfo = [
            'Patient_Info'      => $patientInfo->toArray(),
            'PatientBilSummary' =>  $totalChargesSummary,
            'DoctorsFee'        => $billsPayment,
            'Run_Date'          => Carbon::now()->format('Y/m/d'),
            'Run_Time'          => Carbon::now()->format('g:i A'),
            'Patient_Bill'  => $patientBill->toArray(),
            'Total_Charges' => number_format($totalCharges = $runningBalance, 2),
        ]; 

        $filename   = 'Statement_of_account_summary';
        $html       = view('his.report.soa.statement_of_account_summary', $patientBillInfo)->render();
        $pdf        = PDF::loadHTML($html)->setPaper('letter', 'portrait');

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
        $dompdf->get_canvas()->page_text(554, 24, "{PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0, 0, 0));
        
        // $dompdf->get_canvas()->page_text(100, 780, $pageInfo, $font, 8, array(0, 0, 0));
        return $pdf->stream($filename . '.pdf');
    }
}
