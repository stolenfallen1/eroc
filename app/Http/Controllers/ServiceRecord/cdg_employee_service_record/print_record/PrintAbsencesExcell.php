<?php

namespace App\Http\Controllers\ServiceRecord\cdg_employee_service_record\print_record;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Helpers\Service_Record\UserRequestProcessing;
use App\Helpers\Service_Record\UseDatabaseNormalQuery;
use Carbon\Carbon;

class PrintAbsencesExcell extends Controller
{
    //
    use Exportable;
    protected $request_handler;
    protected $use_query;

    public function __construct(UserRequestProcessing $request_handler, UseDatabaseNormalQuery $use_query)
    {
        $this->request_handler = $request_handler;
        $this->use_query = $use_query;
    }

    public function generatedRecordedAbsences(Request $request)
    {
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
        
            $filename = 'Total_Absences_Each_Department_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
        
            return Excel::download(new class($sumOfAbsences) implements FromArray, WithHeadings {
                protected $sumOfAbsences;
        
                public function __construct($sumOfAbsences)
                {
                    $this->sumOfAbsences = $sumOfAbsences;
                }
        
                public function array(): array
                {
                    $months = [
                        'January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'
                    ];
        
                    $data = [];
        
                    // Step 1: Group data by department
                    $groupedData = [];
                    foreach ($this->sumOfAbsences as $absence) {
                        $dept = $absence->Department;
                        $month = $absence->Month;
                        $count = $absence->AbsentCount;
        
                        if (!isset($groupedData[$dept])) {
                            $groupedData[$dept] = array_fill_keys($months, 0);
                            $groupedData[$dept]['Total'] = 0; // Total column
                        }
        
                        $groupedData[$dept][$month] += $count;
                        $groupedData[$dept]['Total'] += $count;
                    }
        
                    // Step 2: Convert to structured array
                    foreach ($groupedData as $dept => $monthData) {
                        $row = array_merge([$dept], array_values($monthData));
                        $data[] = $row;
                    }
        
                    // Step 3: Compute the bottom row (total absences per month)
                    $totalsRow = ['Total'];
                    foreach ($months as $month) {
                        $totalsRow[] = array_sum(array_column($groupedData, $month));
                    }
                    $totalsRow[] = array_sum(array_column($groupedData, 'Total')); // Grand total
        
                    // Append bottom row
                    $data[] = $totalsRow;
        
                    return $data;
                }

                public function headings(): array
                {
                    return array_merge(['Department'], [
                        'January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December', 'Total'
                    ]);
                }
                public function columnWidths(): array {
                    return [
                        'A' => 30,
                        'B' => 12, 
                        'C' => 12, 
                        'D' => 12, 
                        'E' => 12, 
                        'F' => 12, 
                        'G' => 12, 
                        'H' => 12, 
                        'I' => 12,  
                        'J' => 12,  
                        'K' => 12,  
                        'L' => 12,  
                        'M' => 12,  
                        'N' => 15   
                    ];
                }
            }, $filename);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to fetch sum of absences each department. ' . $e->getMessage()], 500);
        }
    }
}
