<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Therapeuticclass;
use Illuminate\Http\Request;

class TherapeuticClassController extends Controller
{
    public function index()
    {
        return response()->json(['therapeutic_class' => Therapeuticclass::all()], 200);
    }
    public function list()
    {
        try {
            $data = Therapeuticclass::query();
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
            $check_if_exist = Therapeuticclass::select('name')
                       ->where('name', $request->payload['name'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = Therapeuticclass::create([
                    'name' => $request->payload['name'],
                    'isActive' => $request->payload['isActive']
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
            $data['data'] = Therapeuticclass::where('id', $id)->update([
                            'name' => $request->payload['name'],
                            'isActive' => $request->payload['isActive']
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
            $data['data'] = Therapeuticclass::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
