<?php

namespace App\Http\Controllers\BuildFile\Hospital\Setting;

use Illuminate\Http\Request;
use TCG\Voyager\Models\Permission;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Setting\SubModule;

class SubModuleController extends Controller
{
    public function index()
    {
        try {
            $data = SubModule::query();
            if(Request()->module_id && Request()->module_id != 'null') {
                $data->where('module_id', Request()->module_id);
            }
            if(Request()->keyword) {
                $data->where('submodule_description', 'LIKE', '%'.Request()->keyword.'%');
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
            $module_id = $request->payload['module_id'] ?? '';
            $sub_module = $request->payload['submodule_description'] ?? '';
            $driver = $request->payload['driver_id'] ?? '';
            $table_name = str_replace(' ', '', $sub_module);


            $check_if_exist = SubModule::select('submodule_description', 'module_id')->where('submodule_description', $sub_module)->where('module_id', $module_id)->first();
            if(!$check_if_exist) {
                $data = SubModule::create([
                    'submodule_description' => $sub_module,
                    'module_id' =>  $module_id,
                    'isActive' => $request->payload['isActive'],
                ]);
                Permission::generateFor(strtolower($table_name), $driver, $module_id, $data->id, ucwords(strtolower($sub_module)));
                $response['msg'] = 'Success';
                return Response()->json($response, 200);
            }
            $response['msg'] = 'Already Exists!';
            return Response()->json($response, 200);


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

            $module_id = $request->payload['module_id'] ?? '';
            $sub_module = $request->payload['submodule_description'] ?? '';
            $driver = $request->payload['driver_id'] ?? '';
            $table_name = str_replace(' ', '', $sub_module);

            SubModule::where('id', $id)->update([
                'submodule_description' => $sub_module,
                'module_id' => $request->payload['module_id'],
                'isActive' => $request->payload['isActive'],
            ]);

            Permission::generateFor(strtolower($table_name), $driver, $module_id, $id, ucwords(strtolower($sub_module)));

            $response['msg'] = 'Success';
            return Response()->json($response, 200);

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
        $data['data'] = SubModule::where('id', $id)->delete();
        Permission::where('sub_module_id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);

    }
}
