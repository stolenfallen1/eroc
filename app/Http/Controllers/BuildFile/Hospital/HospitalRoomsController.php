<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\mscHospitalRooms;

class HospitalRoomsController extends Controller
{
    public function index()
    {
        try {
            $data = mscHospitalRooms::query();
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
    public function store(Request $request)
    {
        DB::connection("sqlsrv")->beginTransaction();
        try {
            $payload = Request()->form_payload;
            $roomsandbed = mscHospitalRooms::create([
                "room_code" =>isset($payload['room_code']) ? $payload['room_code'] : null,
                "room_description" =>isset($payload['room_description']) ? $payload['room_description'] : null,
                "station_id" =>isset($payload['station_id']) ? $payload['station_id'] : null,
                "total_beds" =>isset($payload['total_beds']) ? $payload['total_beds'] : null,
                "room_class_id" =>isset($payload['room_class_id']) ? $payload['room_class_id'] : null,
                "accomodation_id" =>isset($payload['accomodation_id']) ? $payload['accomodation_id'] : null,
                "room_rate" =>isset($payload['room_rate']) ? $payload['room_rate'] : null,
                "revenue_id" =>'RA',
                "remarks" =>isset($payload['remarks']) ? $payload['remarks'] : null,
                "average_bed" =>isset($payload['average_bed']) ? $payload['average_bed'] : null,
                "room_status_id" =>isset($payload['room_status_id']) ? $payload['room_status_id'] : null,
                "isActive" =>isset($payload['isActive']) ? $payload['isActive'] : null,
                "created_at" =>Carbon::now(),
            ]);

            DB::connection("sqlsrv")->commit();
            return response()->json(['msg' => 'sucess'], 200);

        } catch (\Exception $e) {

            DB::connection("sqlsrv")->rollback();
            return response()->json(["msg" => $e->getMessage()], 200);
        }

    }

     public function update(Request $request,$id)
    {
        DB::connection("sqlsrv")->beginTransaction();
        try {
            $payload = Request()->form_payload;
            $roomsandbed = mscHospitalRooms::where('id',$id)->update([
                "room_code" =>isset($payload['room_code']) ? $payload['room_code'] : null,
                "room_description" =>isset($payload['room_description']) ? $payload['room_description'] : null,
                "station_id" =>isset($payload['station_id']) ? $payload['station_id'] : null,
                "total_beds" =>isset($payload['total_beds']) ? $payload['total_beds'] : null,
                "room_class_id" =>isset($payload['room_class_id']) ? $payload['room_class_id'] : null,
                "accomodation_id" =>isset($payload['accomodation_id']) ? $payload['accomodation_id'] : null,
                "room_rate" =>isset($payload['room_rate']) ? $payload['room_rate'] : null,
                "revenue_id" =>'RA',
                "remarks" =>isset($payload['remarks']) ? $payload['remarks'] : null,
                "average_bed" =>isset($payload['average_bed']) ? $payload['average_bed'] : null,
                "room_status_id" =>isset($payload['room_status_id']) ? $payload['room_status_id'] : null,
                "isActive" =>isset($payload['isActive']) ? $payload['isActive'] : null,
            ]);

            DB::connection("sqlsrv")->commit();
            return response()->json(['msg' => 'sucess'], 200);

        } catch (\Exception $e) {

            DB::connection("sqlsrv")->rollback();
            return response()->json(["msg" => $e->getMessage()], 200);
        }

    }
}
