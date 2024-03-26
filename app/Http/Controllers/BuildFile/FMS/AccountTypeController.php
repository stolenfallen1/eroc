<?php

namespace App\Http\Controllers\BuildFile\FMS;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\AccountType;

class AccountTypeController extends Controller
{
      public function list()
    {
        $data = AccountType::get();
        return response()->json($data, 200);
    }

    public function index()
    {
        $data = AccountType::query();
        if(Request()->keyword) {
            $data->where('account_description', 'LIKE', '%'.Request()->keyword.'%')->orWhere('account_type', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {

        try {
            $check_if_exist = AccountType::select('account_description')
                       ->where('account_description', $request->payload['account_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = AccountType::create([
                    'account_type' => $request->payload['account_type'],
                    'account_description' => $request->payload['account_description'],
                    'account_type' => $request->payload['account_type'],
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

            $data['data'] = AccountType::where('id', $id)->update([
                          'account_type' => $request->payload['account_type'],
                          'account_description' => $request->payload['account_description'],
                          'account_type' => $request->payload['account_type'],
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
        $data['data'] = AccountType::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
