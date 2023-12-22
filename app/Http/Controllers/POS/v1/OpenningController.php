<?php

namespace App\Http\Controllers\POS\v1;


use DB;
use Carbon\Carbon;
use App\Models\POS\Payments;
use Illuminate\Http\Request;
use App\Models\POS\OpenningAmount;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\MscDebitCard;
use App\Models\BuildFile\MscCreditCard;
use App\Models\BuildFile\SystemSequence;
use App\Models\POS\SpReportsSummarySales;
use App\Helpers\PosSearchFilter\Openingbalance;

class OpenningController extends Controller
{
    public function index(Request $request)
    {
        $data['opening'] = OpenningAmount::where('isposted',0)->whereDate('cashonhand_beginning_transaction',Carbon::now()->format('Y-m-d'))->where('shift_code',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->first();
        $data['series'] = SystemSequence::where('terminal_code',Request()->terminal)->where('code', Request()->code)->first();
        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        try{
            $date = Carbon::now()->format('Y-m-d');
            $openingamount = OpenningAmount::whereDate('cashonhand_beginning_transaction',$date)->updateOrCreate(
            ['user_id' => Auth()->user()->idnumber,'shift_code' => Auth()->user()->shift,'terminal_id' => Auth()->user()->terminal_id],
            [
                'cashonhand_beginning_amount'=>Request()->payload['openingamount'] ?? '0',
                'cashonhand_beginning_transaction'=>Carbon::now(),
                'user_id'=>Auth()->user()->idnumber,
                'createdBy'=>Auth()->user()->idnumber,
                'shift_code'=>Auth()->user()->shift,
                'isposted'=>0,
                'terminal_id'=>Auth()->user()->terminal_id,
            ]);
            
            $openingamount->cashonhand_details()->updateOrCreate(['cashonhand_id' => $openingamount->id],
            [
                'denomination_1000' => '0',
                'denomination_500' => '0',
                'denomination_200' => '0',
                'denomination_100' => '0',
                'denomination_50' => '0',
                'denomination_20' => '0',
                'denomination_10' => '0',
                'denomination_5' => '0',
                'denomination_1' => '0',
                'denomination_dot25' => '0',
                'denomination_dot15' => '0',
                'denomination_total' => 0,
                'denomination_checks_total_amount' => '0',
                'createdBy' => Auth()->user()->idnumber,
            ]);
            
            DB::connection('sqlsrv_pos')->commit();
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
            
        }
      
    }

    public function getcard($type)
    {
        if($type == '2'){
            $data['data'] =  MscCreditCard::where('isactive','1')->select('id','description')->get();
        }else if($type == '3'){
            $data['data'] =  MscDebitCard::where('isactive','1')->select('id','description')->get();
        }
        $data['message'] = 'success';
        return response()->json($data, 200);
    }
}