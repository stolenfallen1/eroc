<?php

namespace App\Helpers\Service_Record;
use Illuminate\Support\Facades\DB;

class UseStoredProcedure {
    public function getEmployeeServiceRecords($userRequest) {
        $serviceRecords = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeServiceRecord ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empNum']]);
        return $serviceRecords;
    }

    public function getEmployeeUndertimeRecord($userRequest) {
        $employeeUdertimeSummary = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeUndertimeSummary ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empNum']]);
        return $employeeUdertimeSummary;
    }

    public function getEmployeeTardinessRecord($userRequest) {
        $employeeTardySummary = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeTardySummary ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empNum']]);
        return $employeeTardySummary;
    }

    public function getEmployeeLeavesRecord($userRequest) {
        $employeeLeaves =  DB::connection('sqlsrv_service_record')->select('EXEC sp_employee_leaves @Year = ?, @MonthName = ?, @empnum = ?',[$userRequest['year'], $userRequest['month'], $userRequest['empNum']]);
        return $employeeLeaves;
    }

    public function getEmployeeOTRecord($userRequest) {
        $employeeOT = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeOvertimeSummary ?, ?, ?', [$userRequest['year'], $userRequest['month'], $userRequest['empNum']]);
        return $employeeOT;
    }

    public function getSumOfAbsencesEachDepartment($userRequest) {
        $sumOfAbsences = DB::connection('sqlsrv_service_record')->select('Exec sp_EmployeeServiceRecord_Absences ?, ?',[$userRequest['year'], $userRequest['month']]);
        return $sumOfAbsences;
    }

    public function getEmployeeNonPaidLeavesRecord() {
        $employeeNonPaidLeaves = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeWithoutPaidLeaves');
        return $employeeNonPaidLeaves;
    }

    public function getEmployeePaidLeavesRecord() {
        $employeePaidLeaves = DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeePaidLeaves');
        return $employeePaidLeaves;
    }
}