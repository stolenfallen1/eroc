<?php

namespace App\Http\Controllers\ServiceRecord;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeTbcMaster extends Controller
{
    //
    public function getEmployeeDetail(Request $request) {
        try {
            $lastname   = strtoupper($request->input('lastName'));
            $firstname  = strtoupper($request->input('firstName'));
            $per_page = Request()->per_page ?? 10;
            if(!empty($lastname) && !empty($firstname)) {
                $employeeDetail = DB::connection('sqlsrv_service_record')->table('tbcMaster')
                                    ->select('EmpNum', 'Lastname', 'Firstname')
                                    ->where('Lastname', 'like', '%' . $lastname . '%')
                                    ->where('Firstname', 'like', '%' . $firstname . '%')
                                    ->paginate($per_page);
                if (empty($employeeDetail)) {
                    return response()->json([], 200);
                }
                return response()->json($employeeDetail);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getEmployeeServiceRecords() {
        try{
            $userRequest = $this->getUserRequest();
            $serviceRecords     =   DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeServiceRecord ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empnum']]);
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
            $employeeUdertimeSummary        =   DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeUndertimeSummary ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empnum']]);
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
            $employeeTardySummary           =   DB::connection('sqlsrv_service_record')->select('EXEC sp_EmployeeTardySummary ?, ?, ?',[$userRequest['year'], $userRequest['month'], $userRequest['empnum']]);
            if (empty($employeeTardySummary)) {
                return response()->json([], 200);
            }
            return response()->json($employeeTardySummary);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getPainLeaves() {
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

}
