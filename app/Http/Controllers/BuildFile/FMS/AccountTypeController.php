<?php

namespace App\Http\Controllers\BuildFile\FMS;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\AccountType;

class AccountTypeController extends Controller
{
 
    public function index()
    {
        $data = AccountType::query();
        if(Request()->keyword) {
            $data->where('acct_description', 'LIKE', '%'.Request()->keyword.'%')->orWhere('acct_type', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {

        try {
            $check_if_exist = AccountType::select('acct_description')
                       ->where('acct_description', $request->payload['acct_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = AccountType::create([
                    'acct_type' => $request->payload['acct_type'],
                    'acct_description' => $request->payload['acct_description'],
                    'acct_type' => $request->payload['acct_type'],
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
                          'acct_type' => $request->payload['acct_type'],
                          'acct_description' => $request->payload['acct_description'],
                          'acct_type' => $request->payload['acct_type'],
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
