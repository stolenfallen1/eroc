<?php

namespace App\Http\Controllers\POS;

use DB;
use Carbon\Carbon;
use App\Models\POS\Payments;
use Illuminate\Http\Request;
use App\Models\POS\OpenningAmount;
use App\Http\Controllers\Controller;
use App\Models\POS\SpReportsSummarySales;
use App\Helpers\PosSearchFilter\Openingbalance;

class OpenningAmountTransaction extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['data'] = (new Openingbalance())->searchable();
        $data['message'] = 'success';
        return response()->json($data, 200);
    }

    public function cash_registry()
    {
        $data['data'] = DB::connection('sqlsrv_pos')->table('vw_CashRegistry')->where('type','Opening')->where('shift_code',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->first();
        $data['message'] = 'success';
        return response()->json($data, 200);
    }
    

    public function cash_registry_movement()
    {
        $data['registry'] = DB::connection('sqlsrv_pos')->table('vw_CashRegistry')->where('shift_code',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->get();
        $data['registry_movement'] = DB::connection('sqlsrv_pos')->table('vw_CashRegistry_Movement')->where('shift_code',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->get();
        $data['registry_payment_method'] = DB::connection('sqlsrv_pos')->table('vw_CashRegistry_PaymentMethod')->where('shift_id',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->first();
        $data['message'] = 'success';
        return response()->json($data, 200);
    }

    
    public function Generate_Shift_Sales()
    {
        $result = DB::connection('sqlsrv_pos')->update("EXEC spGenerate_Shift_Sales ?, ?, ?,?,?",
            [
                Auth()->user()->branch_id, 
                Auth()->user()->terminal_id, 
                Auth()->user()->shift, 
                Carbon::now()->format('m/d/Y'), 
                Auth()->user()->idnumber,
        ]);
        return $result;
    }


    public function beginning_transaction()
    {
        
        $payments = new Payments();  
        $opening = new OpenningAmount();

        $spSummaryReport = new SpReportsSummarySales();

        $spSummaryReport->Generate_Shift_Sales();
        $data['data'] = $spSummaryReport->summaryfilterbyreportdate()->first();
        $data['openamount'] = $opening->openamount()->first();
        $cashonhand = $opening->cashonhand()->first();
        $finalbalance =  $payments->nofilterbyreportdate()->get();
        $data['cashonhand'] = $cashonhand ? $cashonhand->cashonhand_beginning_amount : 0;
        $data['openamountid'] = $cashonhand ? $cashonhand->id : 0;
        $data['finalbalance'] = $cashonhand ? $cashonhand->cashonhand_beginning_amount : '0' + $finalbalance[0]['totalamountsales'];
        $data['totalsales'] = $payments->filterbyreportdate()->get();
        $data['payment_transation'] = $payments->movementtransation()->get();
        $data['cashonhanddetails'] =  $opening->details()->with('cashonhand_details','user_details','user_shift')->first();
        
        $data['message'] = 'success';
        return response()->json($data, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        try{
            $date = Carbon::now()->format('Y-m-d');
            $checkifexist =  OpenningAmount::whereDate('cashonhand_beginning_transaction',$date)->where('user_id',Auth()->user()->idnumber)->where('shift_code',Auth()->user()->shift)->where('terminal_id',Auth()->user()->terminal_id)->first();
           
            if(!$checkifexist){
                $openingamount = OpenningAmount::create([
                    'cashonhand_beginning_amount'=>Request()->payload ?? '0',
                    'cashonhand_beginning_transaction'=>Carbon::now(),
                    'user_id'=>Auth()->user()->idnumber,
                    'createdBy'=>Auth()->user()->idnumber,
                    'shift_code'=>Auth()->user()->shift,
                    'isposted'=>0,
                    'terminal_id'=>Auth()->user()->terminal_id,
                ]);
                $openingamount->cashonhand_details()->create(
                    [
                       'denomination_1000' =>'0',
                       'denomination_500' =>'0',
                       'denomination_200' =>'0',
                       'denomination_100' =>'0',
                       'denomination_50' =>'0',
                       'denomination_20' =>'0',
                       'denomination_10' =>'0',
                       'denomination_5' =>'0',
                       'denomination_1' =>'0',
                       'denomination_dot25' =>'0',
                       'denomination_dot15' =>'0',
                       'denomination_total' => 0,
                       'denomination_checks_total_amount' => '0',
                       'createdBy' => Auth()->user()->idnumber,
                    ]
                );
            }else{
                $openingamount = OpenningAmount::whereDate('cashonhand_beginning_transaction',$date)->where('user_id',Auth()->user()->idnumber)->where('shift_code',Auth()->user()->shift)->where('terminal_id',Auth()->user()->terminal_id)->update([
                    'cashonhand_beginning_amount'=>Request()->payload ?? '0',
                    'cashonhand_beginning_transaction'=>Carbon::now(),
                    'user_id'=>Auth()->user()->idnumber,
                    'createdBy'=>Auth()->user()->idnumber,
                    'shift_code'=>Auth()->user()->shift,
                    'sales_batch_number'=>null,
                    'sales_batch_transaction_date'=>null,
                    'report_date'=>null,
                    'posted_date'=>null,
                    'isposted'=>0,
                    'terminal_id'=>Auth()->user()->terminal_id,
                ]);
                // $openingamount->cashonhand_details()->where('cashonhand_id',$checkifexist->id)->update(
                //     [
                //        'denomination_1000' =>'0',
                //        'denomination_500' =>'0',
                //        'denomination_200' =>'0',
                //        'denomination_100' =>'0',
                //        'denomination_50' =>'0',
                //        'denomination_20' =>'0',
                //        'denomination_10' =>'0',
                //        'denomination_5' =>'0',
                //        'denomination_1' =>'0',
                //        'denomination_dot25' =>'0',
                //        'denomination_dot15' =>'0',
                //        'denomination_total' => 0,
                //        'denomination_checks_total_amount' => '0',
                //        'createdBy' => Auth()->user()->idnumber,
                //     ]
                // );
            }
            
            DB::connection('sqlsrv_pos')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
      
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        try{
            $openingamount = OpenningAmount::where('id',$id)->first();
            $openingamount->update([
                'cashonhand_beginning_amount'=>Request()->payload['cashonhand_beginning_amount'] ?? '0',
                'cashonhand_beginning_transaction'=>Carbon::now(),
                'isposted'=>0,
                'shift_code'=>Auth()->user()->shift,
                'updatedBy'=>Auth()->user()->idnumber,
            ]);
            $openingamount->cashonhand_details()->where('cashonhand_id',$id)->update(
                [
                   'denomination_1000' =>'0',
                   'denomination_500' =>'0',
                   'denomination_200' =>'0',
                   'denomination_100' =>'0',
                   'denomination_50' =>'0',
                   'denomination_20' =>'0',
                   'denomination_10' =>'0',
                   'denomination_5' =>'0',
                   'denomination_1' =>'0',
                   'denomination_dot25' =>'0',
                   'denomination_dot15' =>'0',
                   'denomination_total' => 0,
                   'denomination_checks_total_amount' => '0',
                   'updatedBy' => Auth()->user()->idnumber,
                ]
            );
            DB::connection('sqlsrv_pos')->commit();
            return response()->json(["message" =>  'Record successfully update','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
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
        //
    }
}
