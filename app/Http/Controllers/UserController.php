<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use DB;

class UserController extends Controller
{
    public function getDepartmentUsers()
    {
        return response()->json(['users' => User::where('warehouse_id', Auth::user()->warehouse_id)->get()]);
    }

    public function index()
    {
        $data = User::query();
        $data->with("role", "user_department_access");
        if(Request()->keyword) {
            $data->where('lastname', 'LIKE', '%' . Request()->keyword . '%')->orWhere('firstname', 'LIKE', '%' . Request()->keyword . '%')->orWhere('idnumber', 'LIKE', '%' . Request()->keyword . '%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {

        DB::connection('sqlsrv')->beginTransaction();
        try {
            $check_if_exist = User::select('lastname', 'firstname', 'idnumber')
            ->where('lastname', $request->payload['lastname'])
            ->where('firstname', $request->payload['firstname'])
            ->where('idnumber', $request->payload['idnumber'])
            ->exists();
            if(!$check_if_exist) {
                $middlename = isset($request->payload['middlename']) ? $request->payload['middlename'] : '';
                $data['data'] = User::create([
                    'warehouse_id' => (int)$request->payload['warehouse_id'] ?? '',
                    'branch_id' => (int)$request->payload['branch_id'] ?? '',
                    'role_id' => (int)$request->payload['role_id'] ?? '',
                    'firstname' => strtoupper($request->payload['firstname']),
                    'lastname' => strtoupper($request->payload['lastname']),
                    'middlename' => strtoupper($request->payload['middlename'] ?? ''),
                    'birthdate' => $request->payload['birthdate']  ?? '',
                    'email' => strtoupper($request->payload['email'] ?? ''),
                    'name' => strtoupper($request->payload['lastname']) . ', ' . strtoupper($request->payload['firstname']) . ' ' . strtoupper($request->payload['middlename']),
                    'mobileno' => $request->payload['mobileno'] ?? '',
                    'idnumber' => $request->payload['idnumber'] ?? '',
                    'passcode' => $request->payload['passcode'] ?? '',
                    'isactive' => $request->payload['isactive'] ?? '',
                    'updatedby' => Auth()->user()->idnumber,
                    'password' => bcrypt($request->payload['password']),
                ]);
                $data['msg'] = 'Success';
                DB::connection('sqlsrv')->commit();
                return response()->json($data, 200);

            }
            $data['msg'] = 'Already Exists!';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function createdoctor(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $check_if_exist = User::select('lastname', 'firstname', 'idnumber')
               ->where('lastname', $request['lastname'])
               ->where('firstname', $request['firstname'])
               ->where('idnumber', $request['idnumber'])
               ->exists();
            if(!$check_if_exist) {
                User::create([
                    'firstname' => strtoupper($request['firstname']),
                    'lastname' => strtoupper($request['lastname']),
                    'middlename' => strtoupper($request['middlename'] ?? ''),
                    'birthdate' => $request['birthdate']  ?? '',
                    'email' => strtoupper($request['email'] ?? ''),
                    'name' => strtoupper($request['lastname']) . ', ' . strtoupper($request['firstname']) . ' ' . strtoupper($request['middlename']),
                    'mobileno' => $request['mobileno'] ?? '',
                    'idnumber' => $request['idnumber'] ?? '',
                    'passcode' => $request['idnumber'] ?? '',
                    'isactive' => 0,
                    'password' => bcrypt($request['password']),
                ]);
                $data['msg'] = 'Success';
                DB::connection('sqlsrv')->commit();
                return response()->json($data, 200);
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    
    public function update(Request $request, $id)
    {
        try {
            $user = User::where('id', $id)->first();
            $data['data'] = $user->update([
                    'warehouse_id' => (int)$request->payload['warehouse_id'],
                    'branch_id' => (int)$request->payload['branch_id'],
                    'role_id' => (int)$request->payload['role_id'],
                    'firstname' => strtoupper($request->payload['firstname']),
                    'lastname' => strtoupper($request->payload['lastname']),
                    'middlename' => strtoupper($request->payload['middlename'] ?? ''),
                    'birthdate' => $request->payload['birthdate']  ?? '',
                    'email' => strtoupper($request->payload['email'] ?? ''),
                    'name' => strtoupper($request->payload['lastname']) . ', ' . strtoupper($request->payload['firstname']) . ' ' . strtoupper($request->payload['middlename']),
                    'mobileno' => $request->payload['mobileno'] ?? '',
                    'idnumber' => $request->payload['idnumber'] ?? '',
                    'passcode' => $request->payload['passcode'] ?? '',
                    'isactive' => $request->payload['isactive'] ?? '',
                    'updatedby' => Auth()->user()->idnumber,
                    'password' => isset($request->payload['password']) ? bcrypt($request->payload['password']) : $user->password,
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
            $data['data'] = User::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
