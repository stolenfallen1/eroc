<?php
namespace App\Http\Controllers\ServiceRecord;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class DepartmentTbcMaster extends Controller
{
    //
    public function getDeptEmployee(Request $request)
    {
        $departmentCode = $request->input('department');
        $month          = $request->input('month');
        $year           = $request->input('year');
        $status         = $request->input('status');
        try {
            if($status === "1") {
                $employeeList = DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_employeeDepartmentListActive ?', [$departmentCode]);
            } else {
                $employeeList = DB::connection('sqlsrv_service_record')->select('SET NOCOUNT ON; EXEC sp_employeeDepartmentListInActive ?, ?, ?', [$year, $month, $departmentCode]);
            }
            if(empty($employeeList)) {
                return response()->json([], 200);
            }

            return response()->json($employeeList, 200);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDepartmentList() {
        try{
            $departmentList = DB::connection('sqlsrv_service_record')->table('tbcDepartment')
                            ->select('Code', 'Description')
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
}
