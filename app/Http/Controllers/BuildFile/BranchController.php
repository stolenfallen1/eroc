<?php

namespace App\Http\Controllers\BuildFile;

use App\Helpers\Buildfile\Branch;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Branchs;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return response()->json(['branches' => Branchs::all()]);
    }


    public function list()
    {
        return (new Branch())->searchable();
    }

    public function store(Request $request)
    {
        try {
            if(!Branchs::select('name', 'abbreviation')->where('abbreviation', $request->payload['abbreviation'])->where('name', $request->payload['name'])->first()) {
                $data['branch'] = Branchs::create([
                    'name' => $request->payload['name'],
                    'abbreviation' => $request->payload['abbreviation'],
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
            $data['branch'] = Branchs::where('id', $id)->update([
                       'name' => $request->payload['name'],
                       'abbreviation' => $request->payload['abbreviation'],
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
            $data['data'] = Branchs::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
