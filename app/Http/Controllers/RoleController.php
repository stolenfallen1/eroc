<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Models\Role;
use App\Models\RolePermission;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\Permission;
use App\Models\BuildFile\SidebarGroup;

class RoleController extends Controller
{
    public function permission()
    {
        if(Request()->id) {
            return response()->json(['data' => Permission::with('database_driver')->where('module_id', (int) Request()->id)->where('sub_module_id', '!=', 0)->get()]);
        }
        return response()->json(['data' => Permission::with('database_driver')->get()]);
    }

    public function list()
    {
        return response()->json(['data' => Role::orderBy('name', 'asc')->get()]);
    }

    public function index()
    {
        try {
            $data = Role::query();
            if(Request()->keyword) {
                $data->where('display_name', 'LIKE', '%' . Request()->keyword . '%')->orWhere('name', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->orderBy('name', 'asc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function role_permission()
    {
        $data['role'] = Role::with('permissions')->findOrFail(Request()->role_id);
        $data['permission'] = Permission::with('database_driver', 'tablename', 'sidebarGroup')->whereNotNull('module_id')->where('sub_module_id', '0')->orderBy('module', 'asc')->get();
        return response()->json($data, 200);
    }

    public function add_permission(Request $request)
    {

        if($request->payload['type'] == true) {
            $data = RolePermission::insert([
                'permission_id' => $request->payload['id'],
                'role_id' => $request->payload['role_id'],
            ]);
        }
        if($request->payload['type'] == false) {
            $data = RolePermission::where('permission_id', $request->payload['id'])->where('role_id', $request->payload['role_id'])->delete();
        }

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        try {
            $check_if_exist = Role::select('name', 'display_name')->where('display_name', $request->payload['display_name'])->where('name', $request->payload['name'])->first();
            if(!$check_if_exist) {
                $data['data'] = Role::create([
                    'name' => $request->payload['name'],
                    'display_name' => $request->payload['display_name'],
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

            $data['data'] = Role::where('id', $id)->update([
                       'name' => $request->payload['name'],
                       'display_name' => $request->payload['display_name'],
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
            $data['data'] = Role::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
