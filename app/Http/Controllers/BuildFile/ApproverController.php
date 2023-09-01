<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\Approver\InvApprover;
use App\Models\Approver\InvApproverLevel;
use App\Models\User;
use Illuminate\Http\Request;

class ApproverController extends Controller
{
    public function users()
    {
        return response()->json(['data' => User::select('id', 'idnumber', 'name')->get()]);
    }

    public function approver_level()
    {
        return response()->json(['data' => InvApproverLevel::get()]);
    }

    public function list()
    {
        try {
            $data = InvApprover::query();
            $data->with('branch', 'level', 'user_details');
            if(Request()->keyword) {
                $data->where('approver_designation', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = InvApprover::select('approver_designation')
                        ->where('approver_designation', $request->payload['approver_designation'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = InvApprover::create([
                    'branch_id' => $request->payload['branch_id'],
                    'user_id' => $request->payload['user_id'],
                    'approver_id' => $request->payload['approver_id'],
                    'approver_designation' => $request->payload['approver_designation'],
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
            $data['data'] = InvApprover::where('id', $id)->update([
                           'branch_id' => $request->payload['branch_id'],
                           'user_id' => $request->payload['user_id'],
                           'approver_id' => $request->payload['approver_id'],
                           'approver_designation' => $request->payload['approver_designation'],
                           'isActive' => $request->payload['isActive'],
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
            $data['data'] = InvApprover::where('id', $id)->delete();
            $data['msg'] = 'Success';
            return Response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
