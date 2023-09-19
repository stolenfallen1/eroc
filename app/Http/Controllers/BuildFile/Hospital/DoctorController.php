<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
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
