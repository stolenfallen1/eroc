<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Genericnames;
use Illuminate\Http\Request;

class GenericNameController extends Controller
{
    public function index()
    {
        return response()->json(['generic_name' => Genericnames::all()], 200);
    }
    public function list()
    {
        try {
            $data = Genericnames::query();
            if(Request()->keyword) {
                $data->where('generic_names', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Genericnames::select('generic_names')
                       ->where('generic_names', $request->payload['generic_names'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = Genericnames::create([
                    'generic_names' => $request->payload['generic_names'],
                    'generic_key' => $request->payload['generic_key'],
                    'generic_id' => $request->payload['generic_id'],
                    'isactive' => $request->payload['isactive']
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
            $data['data'] = Genericnames::where('id', $id)->update([
                           'generic_names' => $request->payload['generic_names'],
                           'generic_key' => $request->payload['generic_key'],
                           'generic_id' => $request->payload['generic_id'],
                           'isactive' => $request->payload['isactive']
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
            $data['data'] = Genericnames::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
