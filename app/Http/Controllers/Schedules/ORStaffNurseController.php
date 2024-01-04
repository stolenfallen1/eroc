<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedules\ORCirculatingNursesModel;

class ORStaffNurseController extends Controller
{
    public function index()
    {
        $staffnurses = ORCirculatingNursesModel::query();
        if (Request()->keyword) {
            $staffnurses->where('empnum', 'LIKE', '%' . Request()->keyword . '%');
        }
        $staffnurses->orderBy('id','desc');
        $per_page = Request()->per_page == -1 ? 1000 : Request()->per_page;
        return $staffnurses->paginate($per_page);
    }

    public function store(Request $request)
    {
        $staffnurse = ORCirculatingNursesModel::where('empnum', $request->payload['empnum'])->first();
        if (!$staffnurse) {
            // The site doesn't exist, so create a new one
            $staffnurse = ORCirculatingNursesModel::create([
                'empnum' => $request->payload['empnum'],
                'lastname' => $request->payload['lastname'],
                'firstname' => $request->payload['firstname'],
                'middlename' => $request->payload['middlename'],
                'gendercode' => $request->payload['gendercode'],
                'description' => $request->payload['position_id']['position_name'],
                'position_id' => $request->payload['position_id']['id'],
                'isActive' => $request->payload['isActive'],
                // Add other fields as needed
            ]);
            return response()->json(['message' => 'empnum created successfully', 'data' => $staffnurse], 200);
        }

        // If the site already exists, return an error response
        return response()->json(['message' => 'empnum Already Exists'], 301);
    }


    public function update(Request $request, $id)
    {
        $staffnurse = ORCirculatingNursesModel::where('id',$id)->first();
        $staffnurse->update([
            'empnum' => $request->payload['empnum'],
            'lastname' => $request->payload['lastname'],
            'firstname' => $request->payload['firstname'],
            'middlename' => $request->payload['middlename'],
            'gendercode' => $request->payload['gendercode'],
            'description' => isset($request->payload['position_id']['position_name']) ? $request->payload['position_id']['position_name'] : $request->payload['position']['position_name'],
            'position_id' => isset($request->payload['position_id']['id']) ? $request->payload['position_id']['id'] : $request->payload['position']['id'],
            'isActive' => $request->payload['isActive'],
        ]);
        return $staffnurse;
    }

    public function destroy($id)
    {
        $staffnurse = ORCirculatingNursesModel::find($id);
        $staffnurse->delete();
        return $staffnurse;
    }
}
