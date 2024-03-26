<?php

namespace App\Http\Controllers\BuildFile\FMS;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\AccountGroup;

class AccountGroupController extends Controller
{
    public function index()
    {
        $data = AccountGroup::query();
        $data->with("getAccountClass","getAccountType");
        if(Request()->keyword) {
            $data->where('account_group_description', 'LIKE', '%'.Request()->keyword.'%')->orWhere('account_class', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {

        try {
            $check_if_exist = AccountGroup::select('account_group_description')
                       ->where('account_group_code', $request->payload['account_group_code'])
                       ->where('account_group_description', $request->payload['account_group_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = AccountGroup::create([
                    'account_group_code' => $request->payload['account_group_code'],
                    'account_type' => $request->payload['account_type'],
                    'account_group_description' => $request->payload['account_group_description'],
                    'account_class' => $request->payload['account_class'],
                    'remarks' => $request->payload['remarks'],
                    'createdBy' => Auth()->user()->idnumber,
                ]);
                $data['msg'] = 'Success';
                return Response()->json($data, 200);
            }
            $data['msg'] = 'Already Exists!';
            return Response()->json($data, 200);

        } catch (Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }

    }

    public function update(Request $request, $id)
    {

        try {

            $data['data'] = AccountGroup::where('id', $id)->update([
                           'account_group_code' => $request->payload['account_group_code'],
                          'account_type' => $request->payload['account_type'],
                          'account_group_description' => $request->payload['account_group_description'],
                          'account_class' => $request->payload['account_class'],
                           'remarks' => $request->payload['remarks'],
                          'createdBy' => Auth()->user()->idnumber,
                       ]);

            $data['msg'] = 'Success';
            return Response()->json($data, 200);

        } catch (Exception $e) {
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
        $data['data'] = AccountGroup::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
