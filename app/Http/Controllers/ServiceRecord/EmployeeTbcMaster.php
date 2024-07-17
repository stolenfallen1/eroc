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


    public function getEmployeeServiceRecords(Request $request) {
        try{
            $p_year             =   $request->input('year');
            $p_monthName        =   $request->input('month');
            $p_empnum           =   $request->input('empId');
            $serviceRecords     =   DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_EmployeeServiceRecord ?, ?, ?',
                                        [$p_year, $p_monthName, $p_empnum]
                                    );
            if (empty($serviceRecords)) {
                return response()->json([], 200);
            }
            return response()->json($serviceRecords);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getEmployeeLeaves(Request $request) {
        try {
            $year               =   $request->input('year');
            $monthName          =   $request->input('month');
            $empnum             =   $request->input('empId');
            $employeeLeaves     =   DB::connection('sqlsrv_service_record')->select('EXEC sp_employee_leaves @Year = ?, @MonthName = ?, @empnum = ?',
                                        [$year, $monthName, $empnum]
                                    );
            if (empty($employeeLeaves)) {
                return response()->json([], 200);
            }
            return response()->json($employeeLeaves);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getEmployeeUnderTime(Request $request) {
        try {
            $year                           =   $request->input('year');
            $monthName                      =   $request->input('month');
            $empnum                         =   $request->input('empId');
            $employeeUdertimeSummary        =   DB::select('SET NOCOUNT ON; EXEC sp_EmployeeUndertimeSummary ?, ?, ?',
                                                    [$year, $monthName, $empnum]
                                                );
            if (empty($employeeUdertimeSummary)) {
                return response()->json([], 200);
            }
            return response()->json($employeeUdertimeSummary);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getEmployeeTardiness(Request $request) {
        try {
            $year                           =   $request->input('year');
            $monthName                      =   $request->input('month');
            $empnum                         =   $request->input('empId');
            $employeeTardySummary           =   DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_EmployeeTardySummary ?, ?, ?',
                                                    [$year, $monthName, $empnum]
                                                );
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
            $paidLeaves = DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_EmployeePaidLeaves');
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

            $nonPaidLeaves = DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_EmployeeWithoutPaidLeaves');

            if(empty($nonPaidLeaves)) {
                return response()->json([], 200);
            }

            return response($nonPaidLeaves);

        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getEmployeeOT(Request $request) {
        try{
            $p_year             =   $request->input('year');
            $p_monthName        =   $request->input('month');
            $p_empnum           =   $request->input('empId');

            $employeeOT = DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_EmployeeOvertimeSummary ?, ?, ?', [$p_year, $p_monthName, $p_empnum]);

            if(empty($employeeOT)) {
                return response()->json([], 200);
            }

            return response()->json($employeeOT);

        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
