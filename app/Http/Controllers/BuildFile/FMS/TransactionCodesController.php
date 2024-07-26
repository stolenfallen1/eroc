<?php

namespace App\Http\Controllers\BuildFile\FMS;

use App\Models\HIS\his_functions\ExamSpecimenLaboratory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserRevenueCodeAccess;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\FmsExamProcedureItems;
use Illuminate\Support\Facades\DB;

class TransactionCodesController extends Controller
{
    public function index()
    {
        try {
            $data = TransactionCodes::query();
            $data->with('medicare_type');
          
            if(Request()->keyword) {
                $data->where('transaction_description', 'LIKE', '%' . Request()->keyword . '%');
            }
            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '1';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function list() {
        try {
            $data = TransactionCodes::query();
            $data->with('medicare_type');
            return response()->json($data->get(), 200);
        } catch(\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }

    public function add_revenue_access(Request $request){
        $data = UserRevenueCodeAccess::updateOrCreate(
        [
            'user_id'=>$request->idnumber ?? '',
            'revenue_code'=>$request->revenue_code ?? '',
        ],
        [
            'user_id'=>$request->idnumber ?? '',
            'revenue_code'=>$request->revenue_code ?? '',
        ]);
        return response()->json($data,200);
    }

    public function UserRevenueCodeAccess(Request $request){
        $data = UserRevenueCodeAccess::where('user_id', $request->idnumber)->get();
        return response()->json($data, 200);
    }

    public function remove_revenue_access(Request $request){
        UserRevenueCodeAccess::where('user_id',$request->idnumber)->where('revenue_code',$request->revenue_code)->delete();
        return response()->json(['msg'=>'deleted'],200);
    }


    public function revenuecode()
    {
        try {
            $data = TransactionCodes::query();
            $data->with('medicare_type');
            $data->whereIn('id', Auth()->user()->RevenueCode);
            if(Request()->keyword) {
                $data->where('transaction_code', Request()->keyword);
            }
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
            $data = FmsExamProcedureItems::query();
            $data->where('transaction_code', Request()->revenuecode);
            if(Request()->chargecode){
                $data->whereNotIn('map_item_id', Request()->chargecode);
            }
            if(Request()->keyword){
                $data->where('exam_description','LIKE','%'.Request()->keyword.'%');
            }
            $data->with(['prices' => function ($q) {
                $q->where('msc_price_scheme_id', Request()->patienttype);
            }]);

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

    /**
     * FOR HIS CONTROLLERS
     */

    public function chargespecimen(Request $request) 
    {
        DB::beginTransaction();
        try {
            $exam_id = $request->query('map_item_id');
            
            $data = ExamSpecimenLaboratory::with('specimens')
                ->where('exam_id', $exam_id)
                ->get();

            DB::commit();
            return response()->json(['data' => $data], 200);

        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
}
