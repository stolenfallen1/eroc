<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\mscHospitalRoomsStatus;

class HospitalRoomsStatusController extends Controller
{
    public function list()
    {
        $data = mscHospitalRoomsStatus::all();
        return response()->json($data, 200);
    }
    public function index()
    {
        try {
            $data = mscHospitalRoomsStatus::query();

            if(Request()->keyword) {
                $data->where('room_description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
