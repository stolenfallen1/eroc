<?php

namespace App\Http\Controllers\Schedules;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedules\ORDoctorSpecialtyModel;


class ORDoctorSpecialtyController extends Controller
{
    public function index()
    {
        $specialtys = ORDoctorSpecialtyModel::query();
        if (Request()->keyword) {
            $specialtys->where('specialty_name', 'LIKE', '%' . Request()->keyword . '%');
        }
        $per_page = Request()->per_page == -1 ? 1000 : Request()->per_page;
        return $specialtys->paginate($per_page);
    }

    public function store(Request $request)
    {
        $specialty = ORDoctorSpecialtyModel::where('specialty_name', $request->payload['specialty_name'])->first();
        if (!$specialty) {
            // The site doesn't exist, so create a new one
            $specialty = ORDoctorSpecialtyModel::create([
                'specialty_name' => $request->payload['specialty_name'],
                'isactive' => $request->payload['isactive'],
                // Add other fields as needed
            ]);
            return response()->json(['message' => 'specialty_name created successfully', 'data' => $specialty], 200);
        }
        // If the site already exists, return an error response
        return response()->json(['message' => 'specialty_name Already Exists'], 301);
    }


    public function update(Request $request, $id)
    {
        $specialty = ORDoctorSpecialtyModel::find($id);
        $specialty->update([
            'specialty_name' => $request->payload['specialty_name'],
            'isactive' => $request->payload['isactive'],
        ]);
        return $specialty;
    }

    public function destroy($id)
    {
        $specialty = ORDoctorSpecialtyModel::find($id);
        $specialty->delete();
        return $specialty;
    }
}
