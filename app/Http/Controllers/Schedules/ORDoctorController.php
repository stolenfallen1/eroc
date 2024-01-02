<?php

namespace App\Http\Controllers\Schedules;

use Illuminate\Http\Request;
use App\Models\Schedules\ORDoctor;
use App\Http\Controllers\Controller;

class ORDoctorController extends Controller
{
    public function index()
    {
        $staffnurses = ORDoctor::query();
        if (Request()->keyword) {
            $staffnurses->where('doctor_code', 'LIKE', '%' . Request()->keyword . '%');
        }
        $staffnurses->orderBy('id', 'desc');
        $per_page = Request()->per_page == -1 ? 1000 : Request()->per_page;
        return $staffnurses->paginate($per_page);
    }

    public function store(Request $request)
    {
        $staffnurse = ORDoctor::where('doctor_code', $request->payload['doctor_code'])->first();
        if (!$staffnurse) {
            // The site doesn't exist, so create a new one
            $staffnurse = ORDoctor::create([
                'doctor_code' => $request->payload['doctor_code'],
                'lastname' => $request->payload['lastname'],
                'firstname' => $request->payload['firstname'],
                'middlename' => $request->payload['middlename'],
                'description' => $request->payload['specialty_id']['specialty_name'],
                'specialty_id' => $request->payload['specialty_id']['id'],
                'isactive' => $request->payload['isactive'] ?? '',
                // Add other fields as needed
            ]);
            return response()->json(['message' => 'empnum created successfully', 'data' => $staffnurse], 200);
        }

        // If the site already exists, return an error response
        return response()->json(['message' => 'empnum Already Exists'], 301);
    }


    public function update(Request $request, $id)
    {
        $staffnurse = ORDoctor::where('id', $id)->first();
        $staffnurse->update([
            'doctor_code' => $request->payload['doctor_code'],
            'lastname' => $request->payload['lastname'],
            'firstname' => $request->payload['firstname'],
            'middlename' => $request->payload['middlename'],
            'description' => isset($request->payload['specialty_id']['specialty_name']) ? $request->payload['specialty_id']['specialty_name'] : $request->payload['specialty']['specialty_name'],
            'specialty_id' => isset($request->payload['specialty_id']['id']) ? $request->payload['specialty_id']['id'] : $request->payload['specialty']['id'],
            'isactive' => $request->payload['isactive'] ?? '',
        ]);
        return $staffnurse;
    }

    public function destroy($id)
    {
        $staffnurse = ORDoctor::find($id);
        $staffnurse->delete();
        return $staffnurse;
    }
}
