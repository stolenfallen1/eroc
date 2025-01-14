<?php

namespace App\Http\Controllers\ServiceRecord\cdg_employee_service_record\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Dashboard extends Controller
{
    //
    public function index() {
        try {
            $empList = DB::connection('sqlsrv_service_record')
                        ->table('tbcMaster')
                        ->select('Lastname', 'Firstname', 'Middlename')
                        ->where('EmployeeStatusCode', 1)
                        ->get();
            if(empty($empList)) {
                return response()->json([], 400);
            }
            return response()->json($empList, 200);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function serviceRecordDashboard() {
        try {
            $data = DB::connection('sqlsrv_service_record')->select(' EXEC sp_EmployeeServiceDashBoard');
            if(empty($data)) {
                return response()->json([], 400);
            }
            return response()->json($data);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}
