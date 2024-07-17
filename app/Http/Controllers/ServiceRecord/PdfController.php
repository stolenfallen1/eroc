<?php

namespace App\Http\Controllers\ServiceRecord;

use PDF;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;


class PdfController extends Controller
{
    public function generatePDF(Request $request) {
        try {
            $p_year = $request->input('year');
            $p_monthName = $request->input('month');
            $p_empnum = $request->input('empId');

            $employeeLeaves             = DB::select('EXEC sp_employee_leaves @Year = ?, @MonthName = ?, @empnum = ?', [$p_year, $p_monthName, $p_empnum]);
            $serviceRecords             = DB::select('SET NOCOUNT ON; EXEC sp_EmployeeServiceRecord ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);
            $employeeUdertimeSummary    = DB::select('SET NOCOUNT ON; EXEC sp_EmployeeUndertimeSummary ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);
            $employeeTardySummary       = DB::select('SET NOCOUNT ON; EXEC sp_EmployeeTardySummary ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);
            $employeeOT                 = DB::select('SET NOCOUNT ON; EXEC sp_EmployeeOvertimeSummary ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);
            $paidLeaves                 = DB::select('SET NOCOUNT ON; EXEC sp_EmployeePaidLeaves');
            $nonPaidLeaves              = DB::select('SET NOCOUNT ON; EXEC sp_EmployeeWithoutPaidLeaves');

            if (
                    empty($serviceRecords) &&
                    empty($employeeLeaves) &&
                    empty($employeeUdertimeSummary) &&
                    empty($employeeTardySummary) &&
                    empty($paidLeaves) &&
                    empty($nonPaidLeaves)
                ) {
                return response()->json([], 200);
            }

            $employeeName           = $serviceRecords[0]->EmployeeName ?? '';
            $dept                   = $serviceRecords[0]->Department ?? '';
            $pos                    = $serviceRecords[0]->Position ?? '';
            $dateEmployed           = isset($serviceRecords[0]->EmployedDate) ? date('F j, Y', strtotime($serviceRecords[0]->EmployedDate)) : '';
            $regularizationDate     = isset($serviceRecords[0]->RegularizationDate) ? date('F j, Y', strtotime($serviceRecords[0]->RegularizationDate)) : '';
            $resignationDate        = isset($serviceRecords[0]->ResignedDate) ? date('F j, Y', strtotime($serviceRecords[0]->ResignedDate)) : '';
            $resignationYear        = isset($serviceRecords[0]->ResignedDate) ? date('Y', strtotime($serviceRecords[0]->ResignedDate)) : '';

            // Group the data by month
            $groupedData = collect($serviceRecords)->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->TransDate)->format('Y-m');
            })->map(function ($monthData) {
                $codes      = [];
                $days       = [];
                $monthDesc  = [];
                $year       = [];
                foreach ($monthData as $sched) {
                    $code = $sched->Code;
                    if (!in_array($code, ['VL', 'SIL', 'R']) && !in_array($code, $codes)) {
                        $codes[] = $code;
                    }

                    $day = \Carbon\Carbon::parse($sched->TransDate)->day;
                    if (!isset($days[$day])) {
                        $days[$day] = [];
                    }

                    if ($sched->Category === 'O' && !in_array('OT', $days[$day])) {
                        $days[$day][] = 'OT';
                    } elseif ($sched->Category !== 'O' && !in_array($sched->Code, $days[$day])) {
                        $days[$day][] = $sched->Code;
                    }

                    if ($sched->Tardy != ".0000") {
                        $late = $this->formatTime($sched->Tardy, 'L');
                        $days[$day][] = $late;

                    }
                    if ($sched->UnderTime != ".0000") {
                        $undertime = $this->formatTime($sched->UnderTime, 'U');
                        $days[$day][] = $undertime;
                    }
                    if ($sched->Absent != ".0000") {
                        $days[$day][] = 'A';
                    }

                    $formattedMonth = \Carbon\Carbon::parse($sched->TransDate)->format('F');
                    if (!in_array($formattedMonth, $monthDesc)) {
                        $monthDesc[] = $formattedMonth;
                    }

                    $yearValue = \Carbon\Carbon::parse($sched->TransDate)->year;
                    if (!in_array($yearValue, $year)) {
                        $year[] = $yearValue;
                    }
                }

                return [
                    'codes' => $codes,
                    'days' => $days,
                    'monthDesc' => $monthDesc,
                    'year' => $year
                ];
            });

            $data = [
                'groupedData'       => $groupedData->toArray(),
                'year'              => $p_year,
                'employeeId'        => $p_empnum,
                'employeeName'      => strtolower($employeeName),
                'Department'        => strtolower($dept),
                'Position'          => strtolower($pos),
                'dateEmployed'      => $dateEmployed,
                'Regularization'    => $regularizationDate,
                'dateResigned'      => ($resignationYear === "1900" ? "NA" : $resignationDate),
                'EmployeeLeaves'    => $employeeLeaves,
                'EmployeeOT'        => $employeeOT,
                'EmployeeUndertime' => $employeeUdertimeSummary,
                'EmployeeTardiness' => $employeeTardySummary,
                'PaidLeaves'        => $paidLeaves,
                'NonPaidLeaves'     => $nonPaidLeaves,
            ];

            $filename   = str_replace([' ', ','], '-', $employeeName);
            $html       = view('pdf.document', $data)->render();
            $pdf        = PDF::loadHTML($html)->setPaper('letter', 'landscape');

            $pdf->render();
            $dompdf = $pdf->getDomPDF();
            $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
            $dompdf->get_canvas()->page_text(750, 575, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));

            $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d g:i A');
            $dompdf->get_canvas()->page_text(35, 575, $currentDateTime, $font, 10, array(0, 0, 0));
            return $pdf->stream($filename . '.pdf');

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function formatTime($time, $label)
    {
        $hours = floor(intval($time) / 60);
        $minutes = intval($time) % 60;
        $formattedTime = sprintf('%02d:%02d', $hours, $minutes);
        return $label . ':' . intval($time) . 'm' . (intval($time) > 60 ? "\n" . $formattedTime : '');
    }

    private function formatTimeSample($time) {
        preg_match('/(\d+\.?\d*) Hr[s]? (\d+\.?\d*) Min[s]?/', $time, $matches);
        $hourValue = isset($matches[1]) ? floatval($matches[1]) : 0;
        $minuteValue = isset($matches[2]) ? floatval($matches[2]) : 0;
        if ($minuteValue > 0) {
            return $hourValue > 0 ? "{$matches[1]} Hrs {$matches[2]} Mins" : "{$matches[2]} Mins";
        } elseif ($hourValue > 0) {
            return "{$matches[1]} Hrs";
        } else {
            return '0';
        }
    }


    // private function formatTime($time) {
    //     preg_match('/(\d+\.?\d*) Hrs (\d+\.?\d*) Min/', $time, $matches);
    //     $hourValue = isset($matches[1]) ? floatval($matches[1]) : 0;
    //     $minuteValue = isset($matches[2]) ? floatval($matches[2]) : 0;
    //     if ($hourValue > 0 && $minuteValue > 0) {
    //         return "{$matches[1]} Hrs {$matches[2]} Min";
    //     } elseif ($hourValue > 0) {
    //         return "{$matches[1]} Hrs";
    //     } elseif ($minuteValue > 0) {
    //         return "{$matches[2]} Min";
    //     } else {
    //         return '0';
    //     }
    // }
}
