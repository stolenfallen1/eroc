<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Warehouses;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(){
        // return response()->json(['departments' => Warehouses::where('warehouse_Branch_Id', Request()->branch_id)->get() ]);
        return response()->json(['departments' => Warehouses::get() ]);
    }
}
