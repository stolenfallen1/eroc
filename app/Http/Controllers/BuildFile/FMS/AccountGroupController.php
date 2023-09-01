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
        if(Request()->keyword) {
            $data->where('gl_description', 'LIKE', '%'.Request()->keyword.'%')->orWhere('acct_class', 'LIKE', '%'.Request()->keyword.'%');
        }
        $data->orderBy('id', 'desc');
        $page  = Request()->per_page ?? '1';
        return response()->json($data->paginate($page), 200);
    }

    public function store(Request $request)
    {

        try {
            $check_if_exist = AccountGroup::select('gl_description')
                       ->where('gl_description', $request->payload['gl_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = AccountGroup::create([
                    'acct_type' => $request->payload['acct_type'],
                    'gl_description' => $request->payload['gl_description'],
                    'acct_class' => $request->payload['acct_class'],
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
                          'acct_type' => $request->payload['acct_type'],
                          'gl_description' => $request->payload['gl_description'],
                          'acct_class' => $request->payload['acct_class'],
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
