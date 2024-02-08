<?php

namespace App\Http\Controllers;

use DB;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
            $payload = $request->payload;
            // Validation
            $validator = Validator::make($payload, [
                'idnumber' => 'required',
                'lastname' => 'required',
                'firstname' => 'required',
                'middlename' => 'nullable',
                'birthdate' => 'required',
                'role_id' => 'required',
                'branch_id' => 'required',
            ]);

            // Check validation errors
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            // Check if user already exists
            if (User::where('lastname', $request->payload['lastname'])
                ->where('firstname', $request->payload['firstname'])
                ->where('idnumber', $request->payload['idnumber'])
                ->exists()) {
                return response()->json(['msg' => 'Already Exists!'], 200);
            }
            // Create user
            $user = User::create([
                'warehouse_id' => (int) $request->payload['warehouse_id'],
                    'branch_id' => (int) $request->payload['branch_id'],
                    'role_id' => (int) $request->payload['role_id'],
                    'section_id' => isset($request->payload['section_id']) ? (int) $request->payload['section_id'] : NULL,
                    'position_id' => isset($request->payload['position_id']) ? (int) $request->payload['position_id'] : NULL,
                    'firstname' => $request->payload['firstname'],
                    'lastname' => $request->payload['lastname'],
                    'middlename' => $request->payload['middlename'] ?? '',
                    'birthdate' => $request->payload['birthdate']  ?? '',
                    'suffix' => $request->payload['suffix'] ?? '',
                    'email' => $request->payload['email'] ?? '',
                    'name' => $request->payload['lastname'] . ', ' . $request->payload['firstname'] . ' ' . $request->payload['middlename'],
                    'mobileno' => $request->payload['mobileno'] ?? '',
                    'idnumber' => $request->payload['idnumber'] ?? '',
                    'passcode' => $request->payload['passcode'] ?? '',
                    'isactive' => $request->payload['isactive'] ?? '',
                    'updatedby' => auth()->user()->idnumber,
                    'password' => bcrypt($request->payload['password']),
            ]);
            if(isset($request->payload['system'])){
                foreach($request->payload['system'] as $system){
                    $user->systemUserAccess()->create([
                        'subsystem_id'=> $system
                    ]);
                }
            }
           
            $data['msg'] = 'Success';
            DB::connection('sqlsrv')->commit();
            return response()->json($data, 200);

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
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $payload = $request->payload;
            // Validation
            $validator = Validator::make($payload, [
                'idnumber' => 'required',
                'lastname' => 'required',
                'firstname' => 'required',
                'middlename' => 'nullable',
                'birthdate' => 'required',
                'role_id' => 'required',
                'branch_id' => 'required',
            ]);

            // Check validation errors
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::where('id', $id)->first();
            $data['data'] = $user->update([
                    'warehouse_id' => (int) $request->payload['warehouse_id'],
                    'branch_id' => (int) $request->payload['branch_id'],
                    'role_id' => (int) $request->payload['role_id'],
                    'section_id' => isset($request->payload['section_id']) ? (int) $request->payload['section_id'] : NULL,
                    'position_id' => isset($request->payload['position_id']) ? (int) $request->payload['position_id'] : NULL,
                    'firstname' => $request->payload['firstname'],
                    'lastname' => $request->payload['lastname'],
                    'middlename' => $request->payload['middlename'] ?? '',
                    'birthdate' => $request->payload['birthdate']  ?? '',
                    'suffix' => $request->payload['suffix'] ?? '',
                    'email' => $request->payload['email'] ?? '',
                    'name' => $request->payload['lastname'] . ', ' . $request->payload['firstname'] . ' ' . $request->payload['middlename'],
                    'mobileno' => $request->payload['mobileno'] ?? '',
                    'idnumber' => $request->payload['idnumber'] ?? '',
                    'passcode' => $request->payload['passcode'] ?? '',
                    'isactive' => $request->payload['isactive'] ?? '',
                    'updatedby' => Auth()->user()->idnumber,
                    'password' => isset($request->payload['password']) ? bcrypt($request->payload['password']) : $user->password,
                ]);

            if(isset($request->payload['system'])) {
                foreach($request->payload['system'] as $system) {
                    $user->systemUserAccess()->updateOrCreate(
                        [
                            'subsystem_id' => $system,
                            'user_id' => $request->payload['id']
                        ],
                        [
                        'subsystem_id' => $system
                    ]);
                }
                $user->systemUserAccess()->where('user_id', $request->payload['id'])->whereNotIn('subsystem_id', $request->payload['system'])->delete();
            }
          
            $data['msg'] = 'Success';
            DB::connection('sqlsrv')->commit();

            return Response()->json($data, 200);

        } catch (\Exception $e) {
                        
            DB::connection('sqlsrv')->rollback();
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
