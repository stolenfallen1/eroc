<?php

namespace App\Http\Controllers\Schedules;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedules\ORNursePositionModel;


class ORNursePositionController extends Controller
{
    public function index()
    {
        $positions = ORNursePositionModel::query();
        if (Request()->keyword) {
            $positions->where('position_name', 'LIKE', '%' . Request()->keyword . '%');
        }
        $per_page = Request()->per_page == -1 ? 1000 : Request()->per_page;
        return $positions->paginate($per_page);
    }

    public function store(Request $request)
    {
        $position = ORNursePositionModel::where('position_name', $request->payload['position_name'])->first();
        if (!$position) {
            // The site doesn't exist, so create a new one
            $position = ORNursePositionModel::create([
                'position_name' => $request->payload['position_name'],
                'isactive' => $request->payload['isactive'],
                // Add other fields as needed
            ]);
            return response()->json(['message' => 'position_name created successfully', 'data' => $position], 200);
        }

        // If the site already exists, return an error response
        return response()->json(['message' => 'position_name Already Exists'], 301);
    }


    public function update(Request $request, $id)
    {
        $position = ORNursePositionModel::find($id);
        $position->update([
            'position_name' => $request->payload['position_name'],
            'isactive' => $request->payload['isactive'],
        ]);
        return $position;
    }

    public function destroy($id)
    {
        $position = ORNursePositionModel::find($id);
        $position->delete();
        return $position;
    }
}
