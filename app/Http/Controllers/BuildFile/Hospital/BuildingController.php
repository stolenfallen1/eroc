<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AssignStation;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\mscHospitalBldgs;

class BuildingController extends Controller
{
    public function list()
    {
        try {
            $building = mscHospitalBldgs::with('floors',"floors.stations")->where('isActive',1)->get();
            return response()->json($building, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function listofAssignStation(Request $request)
    {
        try {
            $userid = Request()->user_id;
            $building = AssignStation::where('user_id',$userid)->get();
            return response()->json($building, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }


    public function store_assignedstation(Request $request){

        DB::connection('sqlsrv')->beginTransaction();
        try {
            $remove_station = $request->remove_station;
            $selected_station = $request->selected_station;
            $user_id = $request->user_id;

            $station_ids = collect($selected_station)->pluck('station_id')->all();
            $remove_stations = collect($remove_station)->pluck('station_id')->all();

            // Remove existing station_id
            if (!empty($remove_stations)) {
                AssignStation::where('user_id', $user_id)->whereIn('station_id', $remove_stations)->delete();
            }
            // Insert or update station_id
            if (!empty($station_ids)) {
                foreach ($station_ids as $station_id) {
                    AssignStation::updateOrCreate(
                        [
                            'user_id' => $user_id,
                            'station_id' => $station_id,
                        ],
                        [
                            'user_id' => $user_id,
                            'station_id' => $station_id,
                            'created_at' => Carbon::now(),
                        ]
                    );
                }
            }
            DB::connection('sqlsrv')->commit();
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["error" => $e], 200);
        }
        return response()->json(["message" => "Record successfully saved"], 200);

    }
}
