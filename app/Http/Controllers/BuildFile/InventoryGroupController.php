<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Models\BuildFile\ItemGroup;
use App\Http\Controllers\Controller;
use App\Helpers\Buildfile\InventoryGroupFilter;

class InventoryGroupController extends Controller
{
    public function index()
    {
        return ItemGroup::all();
    }

    public function list()
    {
        return (new InventoryGroupFilter())->searchable();
    }

    public function store(Request $request)
    {
        try {
            if(!ItemGroup::select('name', 'inv_code')->where('inv_code', $request->payload['inv_code'])->where('name', $request->payload['name'])->first()) {
                $data['inventoryGroup'] = ItemGroup::create([
                    'name' => $request->payload['name'],
                    'inv_code' => $request->payload['inv_code'],
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
            $data['inventoryGroup'] = ItemGroup::where('id', $id)->update([
                        'name' => $request->payload['name'],
                        'inv_code' => $request->payload['inv_code'],
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
            $data['data'] = ItemGroup::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
