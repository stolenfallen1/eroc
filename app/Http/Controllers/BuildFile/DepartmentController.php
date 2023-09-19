<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Warehouses;
use App\Models\UserDeptAccess;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(){
        // return response()->json(['departments' => Warehouses::where('warehouse_Branch_Id', Request()->branch_id)->get() ]);
        return response()->json(['departments' => Warehouses::get() ]);
    }

     public function add_department_access(Request $request){
        $data = UserDeptAccess::create([
            'user_id'=>$request->idnumber ?? '',
            'warehouse_id'=>$request->department_id ?? '',
        ]);
        return response()->json($data,200);
    }
    public function UserDeptAccess(Request $request){
        $data = UserDeptAccess::where('user_id', $request->idnumber)->get();
        return response()->json($data, 200);
    }

    public function remove_department_access(Request $request){
        UserDeptAccess::where('user_id',$request->idnumber)->where('warehouse_id',$request->department_id)->delete();
        return response()->json(['msg'=>'deleted'],200);
    }
}
