<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Warehouses;
use App\Models\BuildFile\WarehouseSection;
use App\Models\UserDeptAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index(){
        $branch = Request()->branch_id ? Request()->branch_id : Auth()->user()->branch_id;
        $warehouse_id = Request()->warehouse_id;
        $query =  Warehouses::with('sections','subWarehouse')->where('warehouse_Branch_Id',$branch)->where('isWarehouse', 1);
        if($warehouse_id){
            $query->where('id',$warehouse_id);
        }
        return response()->json(['departments' =>$query->get() ]);
        // return response()->json(['departments' => Warehouses::get() ]);
    }
  
    public function departmentlist(){
        $data = Warehouses::query();
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);

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

    public function getSections(){
        $sections = WarehouseSection::where('warehouse_id', Auth::user()->warehouse_id)->get();
        return response()->json(['sections'=> $sections] ,200);
    }
    
    public function getDepartmentList() {
        try {
            $data = Warehouses::query();
            $data->where('isActive', 1);
            $data->orderBy('warehouse_description', 'asc');
            return response()->json($data->get(), 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
