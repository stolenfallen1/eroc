<?php

namespace App\Helpers\Service_Record;

use Illuminate\Support\Facades\DB;
class UseDatabaseNormalQuery {

    public function employeeServiceRecordQuery($userRequest) {
        $year       = $userRequest['year'];
        $empNum     = $userRequest['empNum'];
        $monthNo    = $userRequest['monthNo'];
        $numDays    = $userRequest['numDays'];
        $startDate = '01/01/' . $year;
        $endDate = sprintf('%02d/%02d/%s 23:59', $monthNo, $numDays, $year);
        $serviceRecords = DB::connection('sqlsrv_service_record')
            ->table('CDH_PAYROLL_temp.dbo.vwTimekeeping_DataMerge as A')
            ->select(
                'A.Empnum',
                DB::raw("CONCAT(M.Lastname, ', ', M.FirstName, ' ', CASE WHEN M.MiddleName <> '' THEN LEFT(M.MiddleName, 1) + '.' ELSE '' END) AS EmployeeName"),
                'DEPT.Description AS Department',
                'SECT.Description AS Section',
                'POST.Description AS Position',
                'M.DateEmployed AS EmployedDate',
                'M.RegularizationDate',
                'M.DateResigned AS ResignedDate',
                DB::raw("CASE WHEN M.EmploymentTypeCode = 'R' THEN 'Regular' ELSE 'Casual' END AS EStatus"),
                DB::raw("DATENAME(Month, A.TransDate) AS Month"),
                DB::raw("DATENAME(Day, A.TransDate) AS Day"),
                DB::raw("DATENAME(Year, A.TransDate) AS Year"),
                'A.TransDate',
                DB::raw("ISNULL(A.Code, '') AS Code"),
                'A.TimeIn',
                'A.TimeOut',
                DB::raw("ISNULL(L.TimeIn, L2.TimeIn) AS ActualIn"),
                DB::raw("ISNULL(L.TimeOut, L2.TimeOut) AS ActualOut"),
                DB::raw("
                    CASE
                        WHEN ISNULL(S.Description, '') LIKE '%overload%' THEN 0
                        WHEN A.Code IN ('HD', 'R', 'VL', 'SIL', 'PL', 'ML', 'EL', 'OB', 'HR', 'AL', 'BL', 'UL', 'IL', 'BVL', 'STL', 'WL', 'ED', 'UD', 'SPL') THEN 0
                        ELSE CASE WHEN A.Category = 'W' THEN Tardy ELSE 0 END
                    END AS Tardy
                "),
                DB::raw("
                    CASE
                        WHEN ISNULL(S.Description, '') LIKE '%overload%' THEN 0
                        WHEN A.Code IN ('HD', 'R', 'VL', 'SIL', 'PL', 'ML', 'EL', 'OB', 'HR', 'AL', 'BL', 'UL', 'IL', 'BVL', 'STL', 'WL', 'ED', 'UD', 'SPL') THEN 0
                        ELSE CASE WHEN A.Category = 'W' AND (SELECT SUM(Workmin) FROM CDH_PAYROLL_temp.dbo.vwTimekeeping_Datamerge WHERE Empnum = A.Empnum AND TransDate = A.TransDate AND Category = 'W') <> 0 THEN UnderTime ELSE 0 END
                    END AS UnderTime
                "),
                DB::raw("
                    CASE
                        WHEN ISNULL(S.Description, '') LIKE '%overload%' THEN 0
                        WHEN A.Code IN ('HD', 'R', 'VL', 'SIL', 'PL', 'OB', 'HR', 'AL', 'BL', 'UL', 'IL', 'BVL', 'STL', 'WL', 'ED', 'UD', 'SPL') THEN 0
                        WHEN A.Code IN ('ML', 'EL') THEN 480
                        ELSE CASE WHEN A.Category = 'W' AND (SELECT SUM(Workmin) FROM CDH_PAYROLL_temp.dbo.vwTimekeeping_Datamerge WHERE Empnum = A.Empnum AND TransDate = A.TransDate AND Category = 'W') = 0 THEN ScheduleMin ELSE 0 END
                    END AS Absent
                "),
                'Category'
            )
            ->join('CDH_PAYROLL_temp.dbo.tbcMaster as M', 'A.Empnum', '=', 'M.Empnum')
            ->leftJoin('CDH_PAYROLL_temp.dbo.tmpLogs as L', function ($join) {
                $join->on('L.TransDate', '=', 'A.TransDate')
                    ->on('L.Empnum', '=', 'A.Empnum');
            })
            ->leftJoin('CDH_PAYROLL_temp.dbo.tbcHoliday as H', function ($join) {
                $join->on('H.HolidayDate', '=', 'A.TransDate')
                    ->orOn('H.HolidayDate', '=', 'A.TimeIn')
                    ->orOn('H.HolidayDate', '=', 'A.TimeOut');
            })
            ->leftJoin('CDH_PAYROLL_temp.dbo.tbcSchedType as S', 'A.Type', '=', 'S.Code')
            ->leftJoin('CDH_PAYROLL_temp.dbo.tbcDepartment as DEPT', 'M.DepartmentCode', '=', 'DEPT.Code')
            ->leftJoin('CDH_PAYROLL_temp.dbo.tbcSectionCode as SECT', 'M.SectionCode', '=', 'SECT.Code')
            ->leftJoin('CDH_PAYROLL_temp.dbo.tbcPosition as POST', 'M.PositionCode', '=', 'POST.Code')
            ->leftJoin('CDH_PAYROLL_temp.dbo.tmpLogs as L2', function ($join) {
                $join->on('L2.TransDate', '=', 'A.TransDate')
                    ->on('L2.Empnum', '=', 'A.Empnum');
            })
            ->whereBetween('A.Transdate', [$startDate, $endDate])
            ->where(function ($query) use ($empNum) {
                $query->where('A.Empnum', '=', $empNum)
                    ->orWhereRaw('? = ?', [$empNum, '']);
            })
            ->orderBy('A.TransDate', 'asc')
            ->distinct()
            ->get();
        return $serviceRecords;
    }

    public function employeeUndetimeQuery($userRequest) {
        $year       = $userRequest['year'];
        $empNum     = $userRequest['empNum'];
        $monthNo    = $userRequest['monthNo'];
        $numDays    = $userRequest['numDays'];
        $startDate = '01/01/' . $year;
        $endDate = sprintf('%02d/%02d/%s 23:59', $monthNo, $numDays, $year);
        $excludedCodes = [
            'HD', 'R', 'WL', 'SPL', 'MCW', 'MCSX', 'VAWC', 'VL',
            'EL', 'SIL', 'ML', 'PL', 'S', 'LOA', 'AWOL', 'SLX', 'RX'
        ];

        $query = DB::connection('sqlsrv_service_record')
            ->table('CDH_PAYROLL_temp.dbo.vwTimekeeping_Datamerge as A')
            ->join('CDH_PAYROLL_temp.dbo.tbcMaster as M', 'A.Empnum', '=', 'M.Empnum')
            ->selectRaw("
                YEAR(A.Transdate) as Year,
                MONTH(A.Transdate) as MonthNo,
                DATENAME(MONTH, A.Transdate) as month,
                CONCAT(
                    FLOOR(SUM(A.Undertime) / 60), ' Hr',
                    CASE WHEN FLOOR(SUM(A.Undertime) / 60) > 1 THEN 's' ELSE '' END, ' ',
                    SUM(A.Undertime) % 60, ' Min',
                    CASE WHEN SUM(A.Undertime) % 60 > 1 THEN 's' ELSE '' END
                ) as TotalUndertime,
                COUNT(A.Undertime) as UndertimeCount
            ")
            ->whereBetween('A.Transdate', [$startDate, $endDate])
            ->where(function ($query) use ($empNum) {
                if ($empNum) {
                    $query->where('A.Empnum', $empNum);
                }
            })
            ->where('A.Category', 'W')
            ->whereNotIn('A.Code', $excludedCodes)
            ->where('A.Undertime', '<>', 0)
            ->groupByRaw("YEAR(A.Transdate), MONTH(A.Transdate), DATENAME(MONTH, A.Transdate)")
            ->orderByRaw("YEAR(A.Transdate), MONTH(A.Transdate), DATENAME(MONTH, A.Transdate)");
            $employee_undertime = $query->get();

        return $employee_undertime;
    }

    public function employeeTardinessQuery($userRequest) {
        $year       = $userRequest['year'];
        $empNum     = $userRequest['empNum'];
        $monthNo    = $userRequest['monthNo'];
        $numDays    = $userRequest['numDays'];
        $startDate = '01/01/' . $year;
        $endDate = sprintf('%02d/%02d/%s 23:59', $monthNo, $numDays, $year);
        $excludedCodes = [
            'HD', 'R', 'WL', 'SPL', 'MCW', 'MCSX', 'VAWC', 'VL',
            'EL', 'SIL', 'ML', 'PL', 'S', 'LOA', 'AWOL', 'SLX', 'RX'
        ];
        $query = DB::connection('sqlsrv_service_record')
            ->table('CDH_PAYROLL_temp.dbo.vwTimekeeping_Datamerge as A')
            ->join('CDH_PAYROLL_temp.dbo.tbcMaster as M', 'A.Empnum', '=', 'M.Empnum')
            ->selectRaw("
                YEAR(A.Transdate) as Year,
                MONTH(A.Transdate) as MonthNo,
                DATENAME(MONTH, A.Transdate) as month,
                SUM(A.Tardy) as TotalTardyMinutes,
                COUNT(A.Tardy) as TardyCount
            ")
            ->whereBetween('A.Transdate', [$startDate, $endDate])
            ->where('A.Category', 'W')
            ->where(function ($query) use ($empNum) {
                if ($empNum) {
                    $query->where('A.Empnum', $empNum);
                }
            })
            ->whereNotIn('A.Code', $excludedCodes)
            ->where('A.Tardy', '<>', 0)
            ->groupByRaw("YEAR(A.Transdate), MONTH(A.Transdate), DATENAME(MONTH, A.Transdate)")
            ->orderByRaw("YEAR(A.Transdate), MONTH(A.Transdate), DATENAME(MONTH, A.Transdate)");
            $employeeTardiness = $query->get();
        return $employeeTardiness;
    }

    public function employeeOvertimeQuery($userRequest) {
        $year       = $userRequest['year'];
        $empNum     = $userRequest['empNum'];
        $monthNo    = $userRequest['monthNo'];
        $numDays    = $userRequest['numDays'];
        $startDate = '01/01/' . $year;
        $endDate = sprintf('%02d/%02d/%s 23:59', $monthNo, $numDays, $year);
        $query = DB::connection('sqlsrv_service_record')
            ->table('CDH_PAYROLL_temp.dbo.vwTimekeeping_Datamerge as A')
            ->join('CDH_PAYROLL_temp.dbo.tbcMaster as M', 'A.Empnum', '=', 'M.Empnum')
            ->leftJoin('CDH_PAYROLL_temp.dbo.tmpLogs as L', function ($join) {
                $join->on('L.TransDate', '=', 'A.TransDate')
                    ->on('L.Empnum', '=', 'A.Empnum');
            })
            ->leftJoin('CDH_PAYROLL_temp.dbo.tbcSchedType as S', function ($join) {
                $join->on('A.Type', '=', 'S.Code');
            })
            ->selectRaw("
                A.Empnum as empnum,
                YEAR(A.Transdate) as Year,
                MONTH(A.Transdate) as MonthNo,
                DATENAME(MONTH, A.Transdate) as Month,
                CONCAT(
                    FLOOR(SUM(A.WorkMin) / 60), ' Hr',
                    CASE WHEN FLOOR(SUM(A.WorkMin) / 60) > 1 THEN 's' ELSE '' END, ' ',
                    SUM(A.WorkMin) % 60, ' Min',
                    CASE WHEN SUM(A.WorkMin) % 60 > 1 THEN 's' ELSE '' END
                ) as OTOtalMinutes,
                COUNT(A.WorkMin) as OTCount
            ")
            ->whereBetween('A.Transdate', [$startDate, $endDate])
            ->where('A.Category', 'O')
            ->where(function ($query) use ($empNum) {
                if ($empNum) {
                    $query->where('A.Empnum', $empNum);
                }
            })
            ->groupByRaw("A.Empnum, YEAR(A.Transdate), MONTH(A.Transdate), DATENAME(MONTH, A.Transdate)")
            ->orderByRaw("YEAR(A.Transdate), MONTH(A.Transdate), DATENAME(MONTH, A.Transdate)");
            $employeeOvertime = $query->get();
        return $employeeOvertime;
    }

    public function sumOfAbsentQuery($userRequest) {
        $year       = $userRequest['year'];
        $empNum     = $userRequest['empNum'];
        $monthNo    = $userRequest['monthNo'];
        $numDays    = $userRequest['numDays'];
        $startDate = '01/01/' . $year;
        $endDate = sprintf('%02d/%02d/%s 23:59', $monthNo, $numDays, $year);
        $sumOfAbsences = DB::connection('sqlsrv_service_record')
            ->table('CDH_PAYROLL.dbo.vwDataYear as A')
            ->selectRaw('
                C.Description as Department,
                DateName(Year , A.PayrollDate) as Year,
                DateName(Month , A.PayrollDate) as Month,
                COUNT(A.Empnum) as AbsentCount
            ')
            ->join('CDH_PAYROLL.dbo.tbcMaster as B', 'A.Empnum', '=', 'B.Empnum')
            ->join('CDH_PAYROLL.dbo.tbcDepartment as C', 'A.DepartmentCode', '=', 'C.Code')
            ->whereBetween('A.PayrollDate', [$startDate, $endDate])
            ->where('A.ABSWO', '>', 1)
            ->groupByRaw("C.Code, C.Description, DATENAME(Year, A.PayrollDate), MONTH(A.PayrollDate), DATENAME(Month, A.PayrollDate)")
            ->orderByRaw("C.Description, MONTH(A.PayrollDate)")
            ->get();
        return $sumOfAbsences;
    }

    public function employeeUsedLeaveQuery($userRequest) {
        $year       = $userRequest['year'];
        $empNum     = $userRequest['empNum'];
        $monthNo    = $userRequest['monthNo'];
        $numDays    = $userRequest['numDays'];
        $startDate = '01/01/' . $year;
        $endDate = sprintf('%02d/%02d/%s 23:59', $monthNo, $numDays, $year);
        $employeeComulativeLeaveCount = DB::connection('sqlsrv_service_record')
            ->table('CDH_PAYROLL_temp.dbo.tbtMultiSched as st')
            ->select(
                [
                    'Code', 
                    DB::raw('count(*) as CumulativeCount')
                ])
            ->whereBetween('sDate', [$startDate, $endDate])
            ->where('Empnum', '=', $empNum)
            ->whereIn('Code', ['WL','SPL','MCW','MCSX','VAWC','VL','EL','SIL','ML','PL','S','LOA','AWOL','SLX','RX'])
            ->groupBy('Code')
            ->orderBy('Code', 'asc')
            ->get();
        return $employeeComulativeLeaveCount;
    }

    public function getPaidLeaveListQuery() {
        $paidLeaves = DB::connection('sqlsrv_service_record')
            ->table('CDH_PAYROLL_temp.dbo.tbcShifts as ts')
            ->select('ts.Code', 'ts.Description') 
            ->where('ts.WorkingHrs', '24')
            ->where('ts.WPay', '1')
            ->orderBy('ts.Description', 'asc')
            ->get();
        return $paidLeaves;
    }
    public function getNonPaidLeaveListQuery() {
        $nonPaidLeaves = DB::connection('sqlsrv_service_record')
            ->table('CDH_PAYROLL_temp.dbo.tbcShifts as ts')
            ->select('ts.Code', 'ts.Description') 
            ->where('ts.WorkingHrs', '24')
            ->where('ts.WPay', '0')
            ->orderBy('ts.Description', 'asc')
            ->get();
        return $nonPaidLeaves;
    }

}