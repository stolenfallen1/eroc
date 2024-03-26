<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Genericnames;
use App\Models\BuildFile\mscGenericnames;

class GenericNameController extends Controller
{
    public function index()
    {
        return response()->json(['generic_name' => mscGenericnames::all()], 200);
    }
    public function list()
    {
        try {
            $data = Genericnames::query();
            if(Request()->keyword) {
                $data->where('generic_name', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Genericnames::select('generic_name')->where('generic_name', $request->payload['generic_name'])->first();
            if(!$check_if_exist) {
                $data['data'] = Genericnames::create([
                    'generic_name' => $request->payload['generic_name'],
                    'generic_description' => $request->payload['generic_description'],
                    'isActive' => $request->payload['isActive']
                ]);
                $data['msg'] = 'Record Successfully Saved';
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
                           'generic_name' => $request->payload['generic_name'],
                           'generic_description' => $request->payload['generic_description'],
                           'isActive' => $request->payload['isActive']
                        ]);

            $data['msg'] = 'Record Successfully Update';
            return Response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function destroy($id)
    {
        try {
            $data['data'] = Genericnames::where('id', $id)->delete();
            $data['msg'] = 'Record Successfully Deleted';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
