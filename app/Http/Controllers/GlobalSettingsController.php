<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GlobalSettings;
use App\Models\UserGlobalAccess;
use App\Models\BuildFile\GlobalSetting;
use App\Models\BuildFile\Hospital\Setting\System;

class GlobalSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function index()
    {
        try {
            $data = GlobalSettings::query();
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->where('Systems_id', Request()->system_id);
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function list()
    {
        $data = System::with('globalSettings')->get();
        return response()->json($data,200);
    }

    public function getuseraccess(Request $request)
    {
       $data = UserGlobalAccess::where('user_id', $request->idnumber)->get();
        return response()->json($data,200);
    }

    public function add_user_access(Request $request)
    {
        $data = UserGlobalAccess::create([
            'user_id'=>$request->idnumber,
            'globalsetting_id'=>$request->globalsetting_id,
        ]);
        return response()->json($data,200);
    }

     public function remove_user_access(Request $request)
    {
        UserGlobalAccess::where('user_id',$request->idnumber)->where('globalsetting_id',$request->globalsetting_id)->delete();
        return response()->json(['msg'=>'deleted'],200);
    }

  
    public function store(Request $request)
    {
        
        try {
            $check_if_exist = GlobalSettings::select('description')
                        ->where('description', $request->payload['description'])->where('Systems_id', $request->payload['Systems_id'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = GlobalSettings::create([
                    'setting_code' => $request->payload['setting_code'],
                    'description' => $request->payload['description'],
                    'Systems_id' => $request->payload['Systems_id'],
                    'value' => $request->payload['value'] == 1 ? 'True' : 'False',
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
            $data['data'] = GlobalSettings::where('id', $id)->update([
                        'setting_code' => $request->payload['setting_code'],
                        'description' => $request->payload['description'],
                        'Systems_id' => $request->payload['Systems_id'],
                        'value' => $request->payload['value'] == 'True' ? 'True' : 'False',
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
            $data['data'] = GlobalSettings::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
