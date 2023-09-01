<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\ShiftSchedules;

class ShiftSchedulesController extends Controller
{
    public function index()
    {
        try {

            $data = ShiftSchedules::query();
            if(Request()->keyword) {
                $data->where('shift_description', 'LIKE', '%'.Request()->keyword.'%');
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
        try {
            $check_if_exist = ShiftSchedules::select('shift_description')
                        ->where('shift_description', $request->payload['shift_description'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = ShiftSchedules::create([
                    'shifts_code' => $request->payload['shifts_code'],
                    'shift_description' => $request->payload['shift_description'],
                    'beginning_military_hour' => $request->payload['beginning_military_hour'],
                    'end_military_hour' => $request->payload['end_military_hour'],
                    'isActive' => $request->payload['isActive'],
                ]);
                $data['msg'] = 'Success';
                return Response()->json($data, 200);
            }
            $data['msg'] = 'Already Exists!';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data['data'] = ShiftSchedules::where('id', $id)->update([
                            'shifts_code' => $request->payload['shifts_code'],
                            'shift_description' => $request->payload['shift_description'],
                            'beginning_military_hour' => $request->payload['beginning_military_hour'],
                            'end_military_hour' => $request->payload['end_military_hour'],
                            'isActive' => $request->payload['isActive'],
                         ]);

            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function destroy($id)
    {
        try {
            $data['data'] = ShiftSchedules::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
