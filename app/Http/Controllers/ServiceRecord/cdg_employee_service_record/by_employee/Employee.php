<?php

namespace App\Http\Controllers\ServiceRecord\cdg_employee_service_record\by_employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Employee extends Controller
{
    //
    public function getEmployeeDetail(Request $request) {
        try {
            $lastname   = strtoupper($request->input('lastname'));
            $firstname  = strtoupper($request->input('firstname'));
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
}
