<?php

namespace App\Http\Controllers\BuildFile\Hospital;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Hospital\BankAccounts;
use Illuminate\Http\Request;

class BankAccountsController extends Controller
{
    public function index()
    {

        try {

            $data = BankAccounts::query();
            $data->with('bank');
            if(Request()->keyword) {
                $data->where('bankbranch', 'LIKE', '%'.Request()->keyword.'%');
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
            $check_if_exist = BankAccounts::select('bankbranch')
                        ->where('bankbranch', $request->payload['bankbranch'])
                        ->first();
            if(!$check_if_exist) {
                $data['data'] = BankAccounts::create([
                    'bank_id' => $request->payload['bank_id'],
                    'bankbranch' => $request->payload['bankbranch'],
                    'bankTelNo' => $request->payload['bankTelNo'],
                    'bankAcctName' => $request->payload['bankAcctName'],
                    'bankAcctNo' => $request->payload['bankAcctNo'],
                    'swiftCode' => $request->payload['swiftCode'],
                    'checkno' => $request->payload['checkno'],
                    'contactPersonForBank' => $request->payload['contactPersonForBank'],
                    'contactPersonPosition' => $request->payload['contactPersonPosition'],
                    'website_url' => $request->payload['website_url'],
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
            $data['data'] = BankAccounts::where('id', $id)->update([
                'bank_id' => $request->payload['bank_id'],
                'bankbranch' => $request->payload['bankbranch'],
                'bankTelNo' => $request->payload['bankTelNo'],
                'bankAcctName' => $request->payload['bankAcctName'],
                'bankAcctNo' => $request->payload['bankAcctNo'],
                'swiftCode' => $request->payload['swiftCode'],
                'checkno' => $request->payload['checkno'],
                'contactPersonForBank' => $request->payload['contactPersonForBank'],
                'contactPersonPosition' => $request->payload['contactPersonPosition'],
                'website_url' => $request->payload['website_url'],
                'isactive' => $request->payload['isactive'],
            ]);
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
        $data['data'] = BankAccounts::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
