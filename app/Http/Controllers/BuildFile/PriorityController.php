<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Priority;
use Illuminate\Http\Request;

class PriorityController extends Controller
{
    public function index()
    {
        return response()->json(["priorities" => Priority::get()]);
    }

    public function list()
    {
        try {
            $data = Priority::query();
            if(Request()->keyword) {
                $data->where('priority_description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Priority::select('priority_description')->where('priority_description', $request->payload['priority_description'])->first();
            if(!$check_if_exist) {
                $data['data'] = Priority::create([
                    'priority_description' => $request->payload['priority_description'],
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
            $data['data'] = Priority::where('id', $id)->update([
                            'priority_description' => $request->payload['priority_description'],
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
            $data['data'] = Priority::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
