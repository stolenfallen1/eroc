<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(){
        
        try {
            $data = Doctor::query();
            if(Request()->keyword) {
                $data->where('lastname', 'LIKE', '%' . Request()->keyword . '%')->orWhere('firstname', 'LIKE', '%' . Request()->keyword . '%')->orWhere('doctor_code', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function list()
    {
        try {
            $data = Doctor::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
