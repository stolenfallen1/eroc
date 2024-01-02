<?php

namespace App\Http\Controllers\Schedules;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedules\ORRoomsModel;


class ORRoomsController extends Controller
{
    public function index()
    {
        $room_names = ORRoomsModel::query();
        if (Request()->keyword) {
            $room_names->where('room_name', 'LIKE', '%' . Request()->keyword . '%');
        }
        $per_page = Request()->per_page == -1 ? 1000 : Request()->per_page;
        return $room_names->paginate($per_page);
    }

    public function store(Request $request)
    {
        $room_name = ORRoomsModel::where('room_name', $request->payload['room_name'])->first();
        if (!$room_name) {
            // The site doesn't exist, so create a new one
            $room_name = ORRoomsModel::create([
                'room_name' => $request->payload['room_name'],
                'isactive' => $request->payload['isactive'],
                // Add other fields as needed
            ]);
            return response()->json(['message' => 'room_name created successfully', 'data' => $room_name], 200);
        }

        // If the site already exists, return an error response
        return response()->json(['message' => 'room_name Already Exists'], 301);
    }


    public function update(Request $request, $id)
    {
        $room_name = ORRoomsModel::find($id);
        $room_name->update([
            'room_name' => $request->payload['room_name'],
            'isactive' => $request->payload['isactive'],
        ]);
        return $room_name;
    }

    public function destroy($id)
    {
        $room_name = ORRoomsModel::find($id);
        $room_name->delete();
        return $room_name;
    }
}
