<?php

namespace App\Http\Controllers\BuildFile\Hospital\Setting;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Setting\System;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * 
     */
    public function list()
    {
        try {
            $data = System::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function index()
    {
        try {
            $data = System::query();
            if(Request()->keyword) {
                $data->where('system_system_description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {
            $check_if_exist = System::select('system_description','system_code')
                       ->where('system_description', $request->payload['system_description'])->where('system_code', $request->payload['system_code'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = System::create([
                    'system_description' => $request->payload['system_description'],
                    'system_code' => $request->payload['system_code'],
                    'isActive' => $request->payload['isActive'],
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

 
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        try {
            $data['data'] = System::where('id', $id)->update([
                           'system_description' => $request->payload['system_description'],
                            'system_code' => $request->payload['system_code'],
                           'isActive' => $request->payload['isActive'],
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

        $data['data'] = System::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);

    }
}
