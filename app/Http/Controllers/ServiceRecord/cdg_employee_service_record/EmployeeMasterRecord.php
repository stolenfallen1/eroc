<?php

namespace App\Http\Controllers\ServiceRecord\cdg_employee_service_record;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeMasterRecord extends Controller
{
    //
    public function getEmployeeServiceRecords() {
        try{
            $userRequest = $this->getUserRequest();
            $serviceRecords     =   DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_EmployeeServiceRecord ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empnum']]);
            if (empty($serviceRecords)) {
                return response()->json([], 200);
            }
            return response()->json($serviceRecords);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getEmployeeLeaves() {
        try {
            $userRequest = $this->getUserRequest();
            $employeeLeaves     =   DB::connection('sqlsrv_service_record')->select('EXEC sp_employee_leaves @Year = ?, @MonthName = ?, @empnum = ?',[$userRequest['year'], $userRequest['month'], $userRequest['empnum']]);
            if (empty($employeeLeaves)) {
                return response()->json([], 200);
            }
            return response()->json($employeeLeaves);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getEmployeeUnderTime() {
        try {
            $userRequest = $this->getUserRequest();
            $employeeUdertimeSummary        =   DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_EmployeeUndertimeSummary ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empnum']]);
            if (empty($employeeUdertimeSummary)) {
                return response()->json([], 200);
            }
            return response()->json($employeeUdertimeSummary);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getEmployeeTardiness() {
        try {
            $userRequest = $this->getUserRequest();
            $employeeTardySummary           =   DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_EmployeeTardySummary ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empnum']]);
            if (empty($employeeTardySummary)) {
                return response()->json([], 200);
            }
            return response()->json($employeeTardySummary);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getPaidLeaves() {
        try{
            $paidLeaves = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeePaidLeaves');
            if(empty($paidLeaves)) {
                return response()->json([], 200);
            }

            return response()->json($paidLeaves);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getNonPaidLeave() {
        try{
            $nonPaidLeaves = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeWithoutPaidLeaves');
            if(empty($nonPaidLeaves)) {
                return response()->json([], 200);
            }
            return response($nonPaidLeaves);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getEmployeeOT() {
        try{
            $userRequest = $this->getUserRequest();
            $employeeOT = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeOvertimeSummary ?, ?, ?', [$userRequest['year'], $userRequest['month'], $userRequest['empnum']]);
            if(empty($employeeOT)) {
                return response()->json([], 200);
            }
            return response()->json($employeeOT);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getUserRequest() {
        $request = request();
        $requestParam = [
            'year'  => $request->input('year'),
            'month' => $request->input('month'),
            'empnum' => $request->input('empId')
        ];
        return $requestParam;
    }

    public function getEmployeeServiceRecord() {
        $year = 2023;
        $monthName = 'December';
        $empNum = '05805';
        $month = null;
        $day = 0;
        if($monthName) {
            switch (strtoupper($monthName)) {
                case 'JANUARY':
                    $month = 1;
                    $day = 31;
                    break;
                case 'FEBRUARY':
                    $month = 2;
                    $day = $this->isLeapYear($year) ? 29 : 28;
                    break;
                case 'MARCH':
                    $month = 3;
                    $day = 31;
                    break;
                case 'APRIL':
                    $month = 4;
                    $day = 30;
                    break;
                case 'MAY':
                    $month = 5;
                    $day = 31;
                    break;
                case 'JUNE':
                    $month = 6;
                    $day = 30;
                    break;
                case 'JULY':
                    $month = 7;
                    $day = 31;
                    break;
                case 'AUGUST':
                    $month = 8;
                    $day = 31;
                    break;
                case 'SEPTEMBER':
                    $month = 9;
                    $day = 30;
                    break;
                case 'OCTOBER':
                    $month = 10;
                    $day = 31;
                    break;
                case 'NOVEMBER':
                    $month = 11;
                    $day = 30;
                    break;
                case 'DECEMBER':
                    $month = 12;
                    $day = 31;
                    break;
                default:
                    $month = null;
                    $day = 0;
                    break;
            }
        }

        $startDate = "$year-$month-01";
        $endDate = "$year-$month-$day 23:59:59";

        $records = DB::table('vwTimekeeping_Datamerge as A')
            ->join('tbcMaster as M', 'A.Empnum', '=', 'M.Empnum')
            ->leftJoin('tmpLogs as L', function ($join) {
                $join->on('L.TransDate', '=', 'A.TransDate')
                    ->on('L.Empnum', '=', 'A.Empnum');
            })
            ->leftJoin('tbcHoliday as H', function ($join) {
                $join->on(DB::raw('MONTH(A.TransDate)'), '=', DB::raw('MONTH(H.HolidayDate)'))
                    ->on(DB::raw('DAY(A.TransDate)'), '=', DB::raw('DAY(H.HolidayDate)'))
                    ->where('H.Status', '=', 1);
            })
            ->leftJoin('tbcSchedType as S', 'A.Type', '=', 'S.Code')
            ->leftJoin('tbcDepartment as DEPT', 'M.DepartmentCode', '=', 'DEPT.Code')
            ->leftJoin('tbcSectionCode as SECT', 'M.SectionCode', '=', 'SECT.Code')
            ->leftJoin('tbcPosition as POST', 'M.PositionCode', '=', 'POST.Code')
            ->select(
                'A.Empnum',
                DB::raw("CONCAT(M.Lastname, ', ', M.FirstName, ' ', COALESCE(LEFT(M.MiddleName, 1), ''), '.') as EmployeeName"),
                'DEPT.Description as Department',
                'SECT.Description as Section',
                'POST.Description as Position',
                'M.DateEmployed as EmployedDate',
                'M.RegularizationDate',
                'M.DateResigned',
                DB::raw("CASE WHEN M.EmploymentTypeCode = 'R' THEN 'Regular' ELSE 'Casual' END as EStatus"),
                DB::raw('DATENAME(Month, A.Transdate) as Month'),
                DB::raw('DATENAME(Day, A.Transdate) as Day'),
                DB::raw('DATENAME(Year, A.Transdate) as Year'),
                'A.TransDate',
                DB::raw('COALESCE(A.Code, "") as Code'),
                'A.TimeIn',
                'A.TimeOut',
                DB::raw('COALESCE(L.TimeIn, L.TimeOut) as ActualIn'),
                DB::raw('COALESCE(L.TimeOut, L.TimeOut) as ActualOut'),
                DB::raw("
                    CASE 
                        WHEN S.Description LIKE '%overload%' THEN 0
                        WHEN A.Code IN ('HD', 'R', 'VL', 'SIL', 'PL', 'ML', 'EL', 'OB', 'HR', 'AL', 'BL', 'UL', 'IL', 'BVL', 'STL', 'WL', 'ED', 'UD', 'SPL') THEN 0
                        ELSE CASE WHEN A.Category = 'W' THEN Tardy ELSE 0 END
                    END as Tardy
                "),
                DB::raw("
                    CASE 
                        WHEN S.Description LIKE '%overload%' THEN 0
                        WHEN A.Code IN ('HD', 'R', 'VL', 'SIL', 'PL', 'ML', 'EL', 'OB', 'HR', 'AL', 'BL', 'UL', 'IL', 'BVL', 'STL', 'WL', 'ED', 'UD', 'SPL') THEN 0
                        ELSE CASE WHEN A.Category = 'W' THEN UnderTime ELSE 0 END
                    END as UnderTime
                "),
                DB::raw("
                    CASE 
                        WHEN S.Description LIKE '%overload%' THEN 0
                        WHEN A.Code IN ('HD', 'R', 'VL', 'SIL', 'PL', 'OB', 'HR', 'AL', 'BL', 'UL', 'IL', 'BVL', 'STL', 'WL', 'ED', 'UD', 'SPL') THEN 0
                        ELSE CASE 
                            WHEN A.Category = 'W' THEN ScheduleMin
                            ELSE 0
                        END 
                    END as Absent
                "),
                'A.Category'
            )
            ->where(function ($query) use ($empNum) {
                if (!empty($empNum)) {
                    $query->where('M.Empnum', '=', $empNum);
                }
            })
            ->whereBetween('A.Transdate', [$startDate, $endDate])
            ->distinct()
            ->get();

        return $records;
    }

    public function isLeapYear($year){
        return ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0);
    }
}
