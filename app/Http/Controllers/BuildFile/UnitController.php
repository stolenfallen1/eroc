<?php

namespace App\Http\Controllers\BuildFile;

use Illuminate\Http\Request;
use App\Models\BuildFile\Currency;
use App\Http\Controllers\Controller;
use App\Helpers\Buildfile\UnitFilter;
use App\Models\BuildFile\Unitofmeasurement;

class UnitController extends Controller
{
    public function index()
    {
        return response()->json(['units' => Unitofmeasurement::all()], 200);
    }

    public function list()
    {
        return (new UnitFilter())->searchable();
    }

    public function store(Request $request)
    {
        try {
            if(!Unitofmeasurement::select('name')->where('name', $request->payload['name'])->first()) {
                $data['unit'] = Unitofmeasurement::create([
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
            $data['unit'] = Unitofmeasurement::where('id', $id)->update([
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
            $data['data'] = Unitofmeasurement::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

}
