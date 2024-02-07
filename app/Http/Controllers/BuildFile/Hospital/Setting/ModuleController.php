<?php

namespace App\Http\Controllers\BuildFile\Hospital\Setting;

use Illuminate\Http\Request;
use TCG\Voyager\Models\Permission;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\Setting\Module;
use App\Models\Database\Database;

class ModuleController extends Controller
{
    public function systemsdriver()
    {
        try {
            $data = Database::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }


    public function systemModule()
    {
        try {
            $data = Module::where('system_id', Request()->system_id)->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function list()
    {
        try {
            $data = Module::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function index()
    {
        try {
            $data = Module::query();
            if(Request()->system_id) {
                $data->where('system_id', Request()->system_id);
            }
            if(Request()->keyword) {
                $data->where('module_description', 'LIKE', '%' . Request()->keyword . '%');
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
            $check_if_exist = Module::select('module_description', 'system_id')->where('module_description', $request->payload['module_description'])->where('system_id', $request->payload['system_id'])->first();
            if(!$check_if_exist) {
                $driver = $request->payload['driver_id'] ?? '';
                $module = $request->payload['module_description'] ?? '';
                $sidebar_group_id = $request->payload['sidebar_group_id'] ?? '';
                $table_name = str_replace(' ', '', $module);
                $data = Module::create([
                    'module_description' => $module,
                    'sidebar_group_id' => $request->payload['sidebar_group_id'],
                    'system_id' => $request->payload['system_id'],
                    'database_driver' => $request->payload['driver_id'],
                    'isActive' => $request->payload['isActive'] ?? '',
                ]);
                Permission::generateFor(strtolower($table_name), $driver, $data->id, '', ucwords(strtolower($module)), $sidebar_group_id);
                $response['msg'] = 'Success';
                return Response()->json($response, 200);
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

            $driver = $request->payload['driver_id'] ?? '';
            $module = $request->payload['module_description'] ?? '';
            $sidebar_group_id = $request->payload['sidebar_group_id'] ?? '';
            $table_name = str_replace(' ', '', $module);
            Module::where('id', $id)->update([
                'module_description' => $module,
                'sidebar_group_id' => $request->payload['sidebar_group_id'],
                'system_id' => $request->payload['system_id'],
                'database_driver' => $request->payload['driver_id'],
                'isActive' => $request->payload['isActive'],
            ]);

            Permission::generateFor(strtolower($table_name), $driver, $id, 0, ucwords(strtolower($module)), $sidebar_group_id);
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

        $data['data'] = Module::where('id', $id)->delete();
        Permission::where('module_id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);

    }
}
