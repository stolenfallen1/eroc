<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Warehousegroups;
use Illuminate\Http\Request;

class MscWarehouseGroupController extends Controller
{
    public function list()
    {
        try {
            $data = Warehousegroups::query();
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
            $check_if_exist = Warehousegroups::select('description')
                        ->where('description', $request->payload['description'])
                        ->first();

            if(!$check_if_exist) {
                $data['data'] = Warehousegroups::create([
                    'description' => $request->payload['description'],
                    'prefix' => $request->payload['prefix'],
                    'postfix' => $request->payload['postfix'],
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
            $data['data'] = Warehousegroups::where('id', $id)->update([
                        'description' => $request->payload['description'],
                        'prefix' => $request->payload['prefix'],
                        'postfix' => $request->payload['postfix'],
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
            $data['data'] = Warehousegroups::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
