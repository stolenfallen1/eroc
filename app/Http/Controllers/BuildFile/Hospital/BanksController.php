<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Banks;
use Illuminate\Http\Request;

class BanksController extends Controller
{
    public function index()
    {
        try {

            $data = Banks::query();
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
    public function list()
    {
        $data = Banks::get();
        return response()->json($data, 200);
    }
    public function store(Request $request)
    {

        try {
            $check_if_exist = Banks::select('description')
                     ->where('description', $request->payload['description'])
                     ->first();
            if(!$check_if_exist) {
                $data['data'] = Banks::create([
                    'headquarters_location' => $request->payload['headquarters_location'],
                    'description' => $request->payload['description'],
                    'contact' => $request->payload['contact'],
                    'website_url' => $request->payload['website_url'],
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
            $data['data'] = Banks::where('id', $id)->update([
                          'headquarters_location' => $request->payload['headquarters_location'],
                          'description' => $request->payload['description'],
                          'contact' => $request->payload['contact'],
                          'website_url' => $request->payload['website_url'],
                          'isactive' => $request->payload['isactive'],
                       ]);

            $data['msg'] = 'Success';
            return Response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $data['data'] = Banks::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
