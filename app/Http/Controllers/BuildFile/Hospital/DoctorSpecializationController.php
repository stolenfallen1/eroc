<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\DoctorSpecialization;

class DoctorSpecializationController extends Controller
{
    public function list()
    {
        try {
            $data = DoctorSpecialization::where('isactive',1)->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
