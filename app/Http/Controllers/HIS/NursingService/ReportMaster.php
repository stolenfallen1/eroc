<?php

namespace App\Http\Controllers\HIS\NursingService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use \Carbon\Carbon;
use PDF;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class ReportMaster extends Controller
{
    //
    public function ERDailyCensusReport(Request $request) {
        $reportDate = $request->query('reportDate');
        $today = Carbon::now()->format('Y-m-d');
        if($reportDate !== 'undefined' && $reportDate !== '') {
           
            $data = Patient::whereHas('patientRegistry', function($query) use ( $reportDate) {
                $query->where('mscAccount_Trans_Types', 5)
                      -> whereDate('registry_Date',  $reportDate);
          })->with([
              'sex', 
              'civilStatus', 
              'patientRegistry' => function($query) use ( $reportDate) {
                  $query->whereDate('registry_Date',  $reportDate)
                        ->where('mscAccount_Trans_Types', 5)
                        ->where('isRevoked', 0);
              }
            ])
            ->orderBy('created_at', 'asc')
            ->get();
        } else {
            $data = Patient::whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 5)
                      -> whereDate('registry_Date', $today);
          })->with([
              'sex', 
              'civilStatus', 
              'patientRegistry' => function($query) use ($today) {
                  $query->whereDate('registry_Date', $today)
                        ->where('mscAccount_Trans_Types', 5)
                        ->where('isRevoked', 0);
              }
            ])
            ->orderBy('created_at', 'asc')
            ->get();
        }
    

        $total = $data->count();
        
        $dailyReport = $data->map(function($item) {
            $prefix = '';

            if (intval($item->sex_id) === 1) {
                if (in_array(intval($item->civilstatus_id), [1, 2, 3, 4, 6, 8])) {
                    $prefix = 'Mr';
                }
            }
            elseif (intval($item->sex_id) === 2) {
                if (in_array(intval($item->civilstatus_id), [1, 2, 6, 8])) {
                    $prefix = 'Ms';
                }
            }
            elseif (in_array(intval($item->civilstatus_id), [3, 5, 7])) {
                $prefix = 'Mrs';
            }

            return [
                'patient_id'        => $item->patient_Id,
                'admission_No'      => $item->patientRegistry[0]->case_No,
                'bed_No'            => $item->patientRegistry[0]->er_Bedno ? $item->patientRegistry[0]->er_Bedno : 'ER',
                'patient_name'      => Str::title(Str::lower($item->lastnamae)) . ' ' . Str::title(Str::lower($item->firstname)) . ' ' . Str::title(Str::lower($item->middlename)),
                'name_prefix'       => $prefix,
                'gender'            => $item->sex->sex_description,
                'admission_date'    => date('Y-m-d g:i A', strtotime($item->patientRegistry[0]->registry_Date)),
                'attending_doctor'  => $item->patientRegistry[0]->attending_Doctor_fullname ? $item->patientRegistry[0]->attending_Doctor_fullname : 'ER',
            ];
        });

        $report = [
            'dailyReport' => $dailyReport->toArray(),
            'run_time'    => Carbon::now()->format('g:i A'),
            'run_date'    => Carbon::now()->format('Y-m-d'),
            'grand_total' => $total
        ];

        $filename   = 'Emergency_Daily_Census_Report';
        $html       = view('his.report.daily-census.erDailyReport', $report)->render();
        $pdf        = PDF::loadHTML($html)->setPaper('letter', 'portrait');

        $pdf->render();

        $dompdf = $pdf->getDomPDF();
        $font   = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
        $dompdf->get_canvas()->page_text(478, 78, "{PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0, 0, 0));
        return $pdf->stream($filename . '.pdf');
    }
}
