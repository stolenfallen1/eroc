<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\mscHospitalStation;
use App\Models\HIS\HISHospitalRooms;
use Illuminate\Http\Request;

class HISHospitalRoomsController extends Controller
{
    //
    public function index() 
    {
        try {
            $data = HISHospitalRooms::query();
            $data->where('isActive', 1);
            if (Request()->keyword) {
                $data->where('room_id', 'LIKE', '%'.Request()->keyword.'%');
            }
            if (Request()->station_code) {
                $data->where('station_id', Request()->station_code);
            }

            $data->orderBy('id', 'asc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function getStation() 
    {
        try {
            $data = mscHospitalStation::query();
            $data->where('isactive', 1);
            $data->orderBy('id', 'asc');
            return response()->json($data->get(), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
