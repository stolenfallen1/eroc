<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index()
    {
        try {
            $data = Status::query();
            if(Request()->keyword) {
                $data->where('Status_description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Status::select('Status_description')
                       ->where('Status_description', $request->payload['Status_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = Status::create([
                    'Status_description' => $request->payload['Status_description'],
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
            $data['data'] = Status::where('id', $id)->update([
                            'Status_description' => $request->payload['Status_description'],
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
            $data['data'] = Status::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
