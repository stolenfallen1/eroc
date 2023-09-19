<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\AgeBracket;
use Illuminate\Http\Request;

class AgeBracketController extends Controller
{
    public function index()
    {
        try {

            $data = AgeBracket::query();
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
            $check_if_exist = AgeBracket::select('description')
                       ->where('description', $request->payload['description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = AgeBracket::create([
                    'description' => $request->payload['description'],
                    'min_age' => $request->payload['min_age'],
                    'max_age' => $request->payload['max_age'],
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
            $data['data'] = AgeBracket::where('id', $id)->update([
                          'description' => $request->payload['description'],
                          'min_age' => $request->payload['min_age'],
                          'max_age' => $request->payload['max_age'],
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
        $data['data'] = AgeBracket::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
