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
           
            if(!is_numeric(Request()->keyword)) {
                $patientname = Request()->keyword ?? '';
                $names = explode(',', $patientname); // Split the keyword into firstname and lastname
                $last_name = $names[0];
                $first_name = $names[1]  ?? '';
                if($last_name != '' && $first_name != '') {
                    $data->where('lastname', $last_name);
                    $data->where('firstname', 'LIKE', '' . ltrim($first_name) . '%');
                } else {
                    $data->where('lastname', 'LIKE', '' . $last_name . '%');
                }
            }else{
                $data->where('doctor_code', 'LIKE', '%' . Request()->keyword . '%');
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
