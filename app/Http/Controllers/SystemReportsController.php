<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemReports;
use App\Models\UserAssignedReports;
use App\Models\BuildFile\Hospital\Setting\System;

class SystemReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        $data = System::with('modules')->get();
        return response()->json($data, 200);
    }
    public function assigned_report()
    {
        $data = UserAssignedReports::where('user_id',Request()->idnumber)->get();
        return response()->json($data, 200);
    }

    public function index()
    {
        try {
            $data = SystemReports::query();
            $data->with('module');
            if(Request()->keyword) {
                $data->where('description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->where('system_id', Request()->system_id);
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
            $check_if_exist = SystemReports::select('description')
                        ->where('description', $request->payload['description'])->where('system_id', $request->payload['system_id'])->where('module_id', $request->payload['module_id'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = SystemReports::create([
                    'description' => $request->payload['description'],
                    'module_id' => $request->payload['module_id'],
                     'system_id' => $request->payload['system_id'],
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

    public function update(Request $request, $id)
    {
        try {
            $data['data'] = SystemReports::where('id', $id)->update([
                           'description' => $request->payload['description'],
                           'module_id' => $request->payload['module_id'],
                            'system_id' => $request->payload['system_id'],
                           'isActive' => $request->payload['isActive'],
                        ]);

            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function add_report_access(Request $request){
        $data = UserAssignedReports::create([
                'user_id' => $request->idnumber,
                'report_id' => $request->report_id,
        ]);
        return response()->json($data, 200);

    }
     public function remove_report_access(Request $request){
        UserAssignedReports::where('user_id',$request->idnumber)->where('report_id',$request->report_id)->delete();
        return response()->json(['msg' => 'deleted'], 200);
    }
    public function destroy($id)
    {
        try {
            $data['data'] = SystemReports::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
