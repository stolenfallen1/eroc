<?php

namespace App\Http\Controllers\ServiceRecord\cdg_employee_service_record\print_record;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Service_Record\UseDatabaseNormalQuery;
use App\Helpers\Service_Record\UseStoredProcedure;
use App\Helpers\Service_Record\UserRequestProcessing;
use PDF;

class PrintEmployeeRecord extends Controller{
    protected $request_handler;
    protected $use_query;
    protected $sp;
    public function __construct(UserRequestProcessing $request_handler, UseDatabaseNormalQuery $use_query, UseStoredProcedure $sp) {
        $this->request_handler = $request_handler;
        $this->use_query = $use_query;
        $this->sp = $sp;
    }
    public function generatePDF(Request $request) {
        try {
            $p_year = $request->input('year');
            $p_monthName = $request->input('month');
            $p_empnum = $request->input('empId');

            $employeeLeaves             = DB::connection('sqlsrv_service_record')->select('EXEC sp_employee_leaves @Year = ?, @MonthName = ?, @empnum = ?', [$p_year, $p_monthName, $p_empnum]);
            $serviceRecords             = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeServiceRecord ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);
            $employeeUdertimeSummary    = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeUndertimeSummary ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);
            $employeeTardySummary       = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeTardySummary ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);
            $employeeOT                 = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeOvertimeSummary ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);
            $paidLeaves                 = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeePaidLeaves');
            $nonPaidLeaves              = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeWithoutPaidLeaves');

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
            print_r($employeeLeaves);
            $employeeName           = $serviceRecords[0]->EmployeeName ?? '';
            $section                = $serviceRecords[0]->Section ?? '';
            $dept                   = $serviceRecords[0]->Department ?? '';
            $pos                    = $serviceRecords[0]->Position ?? '';
            $dateEmployed           = isset($serviceRecords[0]->EmployedDate) ? date('F j, Y', strtotime($serviceRecords[0]->EmployedDate)) : '';
            $regularizationDate     = isset($serviceRecords[0]->RegularizationDate) ? date('F j, Y', strtotime($serviceRecords[0]->RegularizationDate)) : '';
            $yearOfRegularization   = isset($serviceRecords[0]->RegularizationDate) ? date('Y', strtotime($serviceRecords[0]->RegularizationDate)) : '';
            $resignationDate        = isset($serviceRecords[0]->ResignedDate) ? date('F j, Y', strtotime($serviceRecords[0]->ResignedDate)) : '';
            $yearResigned           = isset($serviceRecords[0]->ResignedDate) ? date('Y', strtotime($serviceRecords[0]->ResignedDate)) : '';

            $hasFilter = false;
            if($hasFilter) {
                $groupedData = $this->filteredServiceRecords($serviceRecords, $p_monthName);
            } else {
                $groupedData = collect($serviceRecords)->groupBy(function ($item) {
                    return \Carbon\Carbon::parse($item->TransDate)->format('Y-m');
                })->map(function ($monthData, $p_monthName) {
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
            }

            $data = [
                'groupedData'       => $groupedData->toArray(),
                'year'              => $p_year,
                'employeeId'        => $p_empnum,
                'employeeName'      => strtolower($employeeName),
                'Section'           => ($section ? strtolower($section) : "N/A"),
                'Department'        => strtolower($dept),
                'Position'          => strtolower($pos),
                'dateEmployed'      => $dateEmployed,
                'Regularization'    => (intval($yearOfRegularization) === 1900 ? "N/A" : $regularizationDate),
                'dateResigned'      => (intval($yearResigned) === 1900 ? "N/A" : $resignationDate),
                'EmployeeLeaves'    => $employeeLeaves,
                'EmployeeOT'        => $employeeOT,
                'EmployeeUndertime' => $employeeUdertimeSummary,
                'EmployeeTardiness' => $employeeTardySummary,
                'PaidLeaves'        => $paidLeaves,
                'NonPaidLeaves'     => $nonPaidLeaves,
            ];

            $filename   = str_replace([' ', ','], '-', $employeeName);
            $html       = view('service_record.pdf.document', $data)->render();
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

    public function filteredServiceRecords($serviceRecords, $p_monthName) {
        $filteredRecords = collect($serviceRecords)->filter(function ($item) use ($p_monthName) {
            return $item->Month === $p_monthName;
        });

        $groupedData = $filteredRecords->groupBy(function ($item) {
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
        return $groupedData;
    }

    public function generatedRecordedAbsences(Request $request) {
        $recieved_userRequest = [
            'year' => $request->input('year'),
            'month' => $request->input('month'),
            'empnum' => ''
        ];
        $userRequest = $this->request_handler->extractRequestDate($recieved_userRequest);
        try {
            $sumOfAbsences = $this->use_query->sumOfAbsentQuery($userRequest);
            if (empty($sumOfAbsences)) {
                throw new \Exception('No record found');
            }
            $data = [
                'Year' => $recieved_userRequest['year'],
                'sumOfAbsences' => $sumOfAbsences->toArray(),
                'year' => $recieved_userRequest['year'],
                'month' => $recieved_userRequest['month']
            ];
            $filename   = 'Total Absences Each Department';
            $html       = view('service_record.pdf.absences', $data)->render();
            $pdf        = PDF::loadHTML($html)->setPaper('letter', 'landscape');

            $pdf->render();
            $dompdf = $pdf->getDomPDF();
            $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
            $dompdf->get_canvas()->page_text(740, 580, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));
            $currentDateTime = \Carbon\Carbon::now()->format('Y-m-d g:i A');
            $dompdf->get_canvas()->page_text(35, 580, $currentDateTime, $font, 10, array(0, 0, 0));
            return $pdf->stream($filename . '.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch sum of absences each department.' . $e->getMessage()], 500);
            // try {
            //     $sumOfAbsences = $this->sp->getSumOfAbsencesEachDepartment($recieved_userRequest);
            //     if (empty($sumOfAbsences)) {
            //         throw new \Exception('No record found');
            //     }
            //     $data = [
            //         'sumOfAbsences' => $sumOfAbsences,
            //         'year' => $recieved_userRequest['year'],
            //         'month' => $recieved_userRequest['month']
            //     ];
            //     $filename   = 'Total Absences Each Department';
            //     $html       = view('service_record.pdf.absences', $data)->render();
            //     $pdf        = PDF::loadHTML($html)->setPaper('letter', 'landscape');
    
            //     $pdf->render();
            //     $dompdf = $pdf->getDomPDF();
            //     $font = $dompdf->getFontMetrics()->get_font("Montserrat", "normal");
            //     $dompdf->get_canvas()->page_text(750, 575, "{PAGE_NUM} / {PAGE_COUNT}", $font, 10, array(0, 0, 0));
            // } catch (\Exception $fallbackException) {
            //     return response()->json(['error' => 'Unable to fetch sum of absences each department.' . $fallbackException->getMessage()], 500);
            // }
        }
    }

    private function formatTime($time, $label) {
        $hours = floor(intval($time) / 60);
        $minutes = intval($time) % 60;
        $formattedTime = sprintf('%02d:%02d', $hours, $minutes);
        return $label . ':' . intval($time) . 'm' . (intval($time) > 60 ? "\n" . $formattedTime : '');
    }
    
}
