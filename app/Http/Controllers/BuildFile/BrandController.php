<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Models\BuildFile\Brands;
use App\Http\Controllers\Controller;
use App\Helpers\Buildfile\BrandFilter;

class BrandController extends Controller
{
    public function index()
    {
        return response()->json(['brand' => Brands::all()], 200);
    }
    public function list()
    {
        return (new BrandFilter())->searchable();
    }

    public function store(Request $request)
    {
        try {
            if(!Brands::select('name', 'code')->where('code', $request->payload['code'])->where('name', $request->payload['name'])->first()) {
                $data['brand'] = Brands::create([
                    'name' => $request->payload['name'],
                    'code' => $request->payload['code'],
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
            $data['brand'] = Brands::where('id', $id)->update([
                       'name' => $request->payload['name'],
                       'code' => $request->payload['code'],
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
            $data['data'] = Brands::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
