<?php

namespace App\Http\Controllers\ServiceRecord\cdg_employee_service_record\by_department;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Department extends Controller
{
    //
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
