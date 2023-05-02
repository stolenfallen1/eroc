<?php

namespace App\Http\Controllers\POS;

use DB;
use Illuminate\Http\Request;
use App\Models\POS\OpenningAmount;
use App\Http\Controllers\Controller;
use App\Helpers\PosSearchFilter\Openingbalance;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\POS\Payments;
use Carbon\Carbon;

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
        $from = Carbon::now()->format('Y-m-d');
        $to = Carbon::now()->format('Y-m-d').' 23:59';
        if(Auth()->user()->shift == '106N'){
            $from = Carbon::now()->subDays(1)->format('Y-m-d');
            $to = Carbon::now()->addDay(1)->format('Y-m-d H:i');
        }
        if(Auth()->user()->shift == '62N'){
            $from = Carbon::now()->subDays(1)->format('Y-m-d');
            $to = Carbon::now()->addDay(1)->format('Y-m-d H:i');
        }
        $data['total_cash_sales'] = Payments::where('createdBy', Auth()->user()->id)->whereBetween('payment_date',[$from,$to])->where('payment_method_id',['1'])->selectRaw('(SUM(payment_amount_due) + SUM(payment_vatable_amount)) as totalamountsales,SUM(payment_received_amount) as totalcashtendered,SUM(payment_changed_amount) as totalchangedamount')->get();
        $data['totalsales'] = Payments::where('createdBy', Auth()->user()->id)->whereBetween('payment_date',[$from,$to])->selectRaw('(SUM(payment_amount_due) + SUM(payment_vatable_amount)) as totalamountsales,SUM(payment_received_amount) as totalcashtendered,SUM(payment_changed_amount) as totalchangedamount')->get();
        $data['totalchecks'] = Payments::where('createdBy', Auth()->user()->id)->whereBetween('payment_date',[$from,$to])->whereIn('payment_method_id',['6'])->selectRaw('(SUM(payment_amount_due) + SUM(payment_vatable_amount)) as totalamountsales,SUM(payment_received_amount) as totalcashtendered,SUM(payment_changed_amount) as totalchangedamount')->get();
        $data['totalcard'] = Payments::where('createdBy', Auth()->user()->id)->whereBetween('payment_date',[$from,$to])->whereIn('payment_method_id',['2','3','4','5'])->selectRaw('(SUM(payment_amount_due) + SUM(payment_vatable_amount)) as totalamountsales,SUM(payment_received_amount) as totalcashtendered,SUM(payment_changed_amount) as totalchangedamount')->get();
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
            $openingamount = OpenningAmount::create([
                'cashonhand_beginning_amount'=>Request()->payload['cashonhand_beginning_amount'] ?? '0',
                'cashonhand_beginning_transaction'=>Carbon::now(),
                'user_id'=>Auth()->user()->id,
                'createdBy'=>Auth()->user()->id,
                'shift_code'=>Auth()->user()->shift,
                'isposted'=>0,
                'terminal_id'=>(new Terminal)->terminal_details()->id,
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
                   'createdBy' => Auth()->user()->id,
                ]
            );
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
                'updatedBy'=>Auth()->user()->id,
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
                   'updatedBy' => Auth()->user()->id,
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
