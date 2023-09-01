<?php

namespace App\Http\Controllers\BuildFile\vendor;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\vendor\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    public function index()
    {
        try {
            $data = Type::query();
            if(Request()->keyword) {
                $data->where('name', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Type::select('name')->where('name', $request->payload['name'])->first();
            if(!$check_if_exist) {
                $data['data'] = Type::create([
                    'code' => $request->payload['code'],
                    'name' => $request->payload['name'],
                    'isactive' => $request->payload['isactive'],
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
            $data['data'] = Type::where('id', $id)->update([
                           'code' => $request->payload['code'],
                           'name' => $request->payload['name'],
                           'isactive' => $request->payload['isactive'],
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
            $data['data'] = Type::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
