<?php

namespace App\Http\Controllers\ServiceRecord\cdg_employee_service_record\by_department;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Service_Record\UserRequestProcessing;

class Department extends Controller {
    //
    protected $request_handler;

    public function __construct(UserRequestProcessing $request_handler) {
        $this->request_handler = $request_handler;
    }
    public function getDeptEmployee(Request $request) {
        $departmentCode = $request->input('department');
        $month          = $request->input('month');
        $year           = $request->input('year');
        $status         = $request->input('status');

        try {
            if($status === "1") {
                $employeeList = DB::connection('sqlsrv_service_record')->select(' EXEC sp_employeeDepartmentListActive ?', [$departmentCode]);
            } else {
                $employeeList = DB::connection('sqlsrv_service_record')->select(' EXEC sp_employeeDepartmentListInActive ?, ?, ?', [$year, $month, $departmentCode]);
            }
            if(empty($employeeList)) {
                return response()->json([], 200);
            }
            $employeeList = collect($employeeList)->map(function ($item) {
                $item->Lastname     = $this->strTransform($item->Lastname);
                $item->Firstname    = $this->strTransform($item->Firstname);
                $item->Middlename   = $this->strTransform($item->Middlename);
                return $item;
            });
            
            return response()->json($employeeList, 200);

        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getInActiveEmployeeList($request) {
        $departmentCode = $request->input('department');
        $received_userRequest = [
            'year'      => $request->input('year'),
            'month'     => $request->input('month'),
            'empnum'    => ''
        ];
        $userRequest = $this->request_handler->extractRequestDate($received_userRequest);
        $startDate = '01/01/' . $userRequest['year'];
        $endDate = sprintf('%02d/%02d/%s 23:59', $userRequest['monthNo'], $userRequest['numDays'], $userRequest['year']);
        try {
            $inActiveEmployeeList = DB::connection('sqlsrv_service_record')
                ->table('CDH_PAYROLL.dbo.tbcMaster as a')
                ->selectRaw("
                    Lastname,
                    Firstname,
                    Middlename,
                    b.Description,
                    a.EmployeeStatusCode
                ")
                ->join('CDH_PAYROLL.dbo.tbcPosition as b', 'b.Code', '=', 'a.PositionCode')
                ->where('a.EmployeeStatusCode', '=', 2)
                ->where('a.DepartmentCode', '=', $departmentCode)
                ->whereBetween('a.DateResigned', [$startDate, $endDate])
                ->orderBy('Lastname', 'ASC')
                ->get();
            return response()->json($inActiveEmployeeList, 200);

        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDepartmentList() {
        try{
            $departmentList = DB::connection('sqlsrv_service_record')->table('tbcDepartment')
                            ->select('Code', 'Description')
                            ->where('Status', 1)
                            ->orderBy('Description', 'ASC')
                            ->get();

            if(empty($departmentList)) {
                return response()->json([], 200);
            }
            $departmentList->transform(function ($item) {
                $item->Description = ucwords(strtolower($item->Description));
                return $item;
            });

            return response()->json($departmentList);

        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function strTransform($string) {
        $is_Found = strpos($string, 'Ñ');
        if($is_Found) {
            $temp = str_replace('Ñ', '165', $string);
            $lowerCaseStr = ucwords(strtolower($temp));
            $newString = str_replace('165', 'ñ', $lowerCaseStr);
        } else {
            $newString = ucwords(strtolower($string));
        }
        return $newString;
    }
}
