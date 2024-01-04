<?php

namespace App\Http\Controllers\BuildFile\FMS;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\FmsExamProcedureItems;

class TransactionCodesController extends Controller
{
    public function index()
    {
        try {
            $data = TransactionCodes::query();
            $data->with('medicare_type');
            if(Request()->keyword) {
                $data->where('transaction_description', 'LIKE', '%'.Request()->keyword.'%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    
    public function revenuecode()
    {
        try {
            $data = TransactionCodes::query();
            $data->with('medicare_type');
            $data->where('transaction_code',Request()->keyword);
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
     public function chargingcode()
    {
        try {
            $data = FmsExamProcedureItems::where('transaction_code',Request()->revenuecode)->get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $check_if_exist = TransactionCodes::select('transaction_description')
                       ->where('transaction_description', $request->payload['transaction_description'])
                       ->first();
            if(!$check_if_exist) {
                $data['data'] = TransactionCodes::create([
                    'transaction_code' => $request->payload['transaction_code'],
                    'transaction_description' => $request->payload['transaction_description'],
                    'DrCr' => $request->payload['DrCr'],
                    'LGRP' => $request->payload['LGRP'],
                    'Medicare_Type_id' => $request->payload['Medicare_Type_id'],
                    'isActive' => $request->payload['isActive'],
                    'createdBy' => Auth()->user()->idnumber,
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
            $data['data'] = TransactionCodes::where('id', $id)->update([
                    'transaction_code' => $request->payload['transaction_code'],
                    'transaction_description' => $request->payload['transaction_description'],
                    'DrCr' => $request->payload['DrCr'],
                    'LGRP' => $request->payload['LGRP'],
                    'Medicare_Type_id' => $request->payload['Medicare_Type_id'],
                    'isActive' => $request->payload['isActive'],
                    'createdBy' => Auth()->user()->idnumber,
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
        $data['data'] = TransactionCodes::where('id', $id)->delete();
        $data['msg'] = 'Success';
        return Response()->json($data, 200);
    }
}
