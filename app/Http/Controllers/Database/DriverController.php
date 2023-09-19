<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;
use App\Models\Database\Database;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $data = Database::query();
            if(Request()->keyword) {
                $data->where('name', 'LIKE', '%'.Request()->keyword.'%')->orWhere('description', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = Database::select('name','driver')
                       ->where('name', $request->payload['name'])->where('driver', $request->payload['driver'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = Database::create([
                    'name' => $request->payload['name'],
                    'driver' => $request->payload['driver'],
                    'ipaddress' => $request->payload['ipaddress'],
                    'description' => $request->payload['description']
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
            $data['data'] = Database::where('id', $id)->update([
                           'name' => $request->payload['name'],
                           'driver' => $request->payload['driver'],
                           'ipaddress' => $request->payload['ipaddress'],
                           'description' => $request->payload['description']
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
            $data['data'] = Database::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
