<?php

namespace App\Http\Controllers\BuildFile\vendor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\vendor\Terms;

class TermsController extends Controller
{
    public function index()
    {
        try {
            $data = Terms::query();
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Terms::select('description')->where('description', $request->payload['description'])->first();
            if(!$check_if_exist) {
                $data['data'] = Terms::create([
                    'code' => $request->payload['code'],
                    'days' => $request->payload['days'],
                    'description' => $request->payload['description'],
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
            $data['data'] = Terms::where('id', $id)->update([
                           'code' => $request->payload['code'],
                           'description' => $request->payload['description'],
                           'days' => $request->payload['days'],
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
            $data['data'] = Terms::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
