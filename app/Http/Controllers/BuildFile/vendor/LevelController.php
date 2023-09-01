<?php

namespace App\Http\Controllers\BuildFile\vendor;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\vendor\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    public function index()
    {
        try {
            $data = Level::query();
            if(Request()->keyword) {
                $data->where('level', 'LIKE', '%'.Request()->keyword.'%');
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

            $check_if_exist = Level::select('level')
                        ->where('level', $request->payload['level'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = Level::create([
                    'level' => $request->payload['level'],
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
            $data['data'] = Level::where('id', $id)->update([
                          'level' => $request->payload['level'],
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
            $data['data'] = Level::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
