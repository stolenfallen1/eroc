<?php

namespace App\Http\Controllers\POS;
use DB;
use Carbon\Carbon;
use App\Models\POS\Payments;
use Illuminate\Http\Request;
use App\Models\POS\OpenningAmount;
use App\Http\Controllers\Controller;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;

class ClosingTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        DB::connection('sqlsrv')->beginTransaction();
        try{

            $type = Request()->payload['registrytype'] ?? '';
            $terminal = (new Terminal)->terminal_details();
            $sales_batch = (new SeriesNo())->get_sequence('SBN',$terminal->terminal_code);
            $sales_sequenceno = (new SeriesNo())->generate_series($sales_batch->seq_no, $sales_batch->digit);
          
           
            if($type == 'posting'){
                $id = Request()->payload['registryid'] ?? '';
                $closingtransaction = OpenningAmount::where('id',$id)->whereDate('cashonhand_beginning_transaction','=',Carbon::now()->format('Y-m-d'))->whereNull('sales_batch_number')->where('shift_code',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->first();
                $cashonhand['isposted'] = '1';
                $cashonhand['sales_batch_number'] = $sales_sequenceno;
                $cashonhand['sales_batch_transaction_date'] = Carbon::now();
                $cashonhand['postedby'] = Auth()->user()->idnumber;
                $cashonhand['posted_date'] = Carbon::now();
            }else{
                $closingtransaction = OpenningAmount::whereDate('cashonhand_beginning_transaction','=',Carbon::now()->format('Y-m-d'))->whereNull('sales_batch_number')->where('shift_code',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->first();
                $id = $closingtransaction->id;
            }
            $closing = DB::connection('sqlsrv_pos')->table('vw_CashRegistry_Closing')->where('shift_id',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->first();
           
            $cashonhand['cashonhand_add_uncollected_card_day'] = (float)Request()->payload['cashonhand_add_uncollected_card_day'] ?? '';
            $cashonhand['cashonhand_less_collected_card_amount'] = (float)Request()->payload['cashonhand_less_collected_card_amount'] ?? '';
            $cashonhand['cashonhand_net_collections_for_the_day'] = (float)Request()->payload['cashonhand_net_collections_for_the_day'] ?? '';
            $cashonhand['cashonhand_overage_shortages'] = (float)Request()->payload['cashonhand_overage_shortages'] ?? '';
            $cashonhand['cashonhand_total_cash_check_collection_amount'] = (float)Request()->payload['cashonhand_total_cash_check_collection_amount'] ?? '';
            $cashonhand['cashonhand_total_collection_amount'] = (float)Request()->payload['cashonhand_total_collection_amount'] ?? '';
            $cashonhand['cashonhand_total_tendered_amount'] = (float)Request()->payload['cashonhand_total_tendered_amount'] ?? '';
            if($closing){
                $cashonhand['cashonhand_system_total_cash_tendered_amount'] = (float)$closing->system_total_sales ?? '';
                $cashonhand['cashonhand_system_total_cash_sales_amount'] = (float)$closing->system_cash_tendered ?? '';
                $cashonhand['cashonhand_system_total_cash_changed_amount'] = (float)$closing->system_changed_amount ?? '';
            }
          
            $cashonhand['terminal_id'] =(new Terminal)->terminal_details()->id;
            $cashonhand['cashonhand_closing_transaction'] = Carbon::now();
            $cashonhand['report_date'] =Carbon::now()->format('Y-m-d');
            $cashonhand['updatedBy'] =Auth()->user()->idnumber;
            $closingtransaction->update($cashonhand);
            $closingdetails = [];
            $totalamount = 0;
            foreach(Request()->openingitems as $row){
                $totalamount += (float)$row['denomination_amount'] * $row['denomination_qty'];
                if($row['id'] == '0'){
                    $closingdetails['denomination_1000'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '1'){
                    $closingdetails['denomination_500'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '2'){
                    $closingdetails['denomination_200'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '3'){
                    $closingdetails['denomination_100'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '4'){
                    $closingdetails['denomination_50'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '5'){
                    $closingdetails['denomination_20'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '6'){
                    $closingdetails['denomination_10'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '7'){
                    $closingdetails['denomination_5'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '8'){
                    $closingdetails['denomination_1'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '9'){
                    $closingdetails['denomination_dot25'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '10'){
                    $closingdetails['denomination_dot15'] = (float)$row['denomination_qty'];
                }
            }
            $closingdetails['denomination_total'] = (float)Request()->payload['totalamountcash'] ?? '';
            $closingdetails['denomination_checks_total_amount'] = (float)Request()->payload['totalchecks'] ?? '';
            $closingtransaction->cashonhand_details()->where('cashonhand_id',$id)->update($closingdetails);
           
            if($type == 'posting'){
                $sales_seriesno = $sales_batch->seq_no + 1;
                $sales_batch->update([
                    'seq_no'=>$sales_seriesno,
                    'recent_generated'=>$sales_sequenceno,
                ]);

                $shift = DB::connection('sqlsrv')->table('mscShiftSchedules')->where('shifts_code',Auth()->user()->shift)->first();
                $from = Carbon::now()->format('Y-m-d').' '.$shift->beginning_military_hour.':00:00';
                $to = Carbon::now()->format('Y-m-d').' '.$shift->end_military_hour.':59:59';

                Payments::where('createdBy', Auth()->user()->idnumber)->whereNull('sales_batch_number')->whereBetween('payment_date',[$from,$to])->update([
                    'isposted' => '1',
                    'sales_batch_number' => $sales_sequenceno,
                    'sales_batch_transaction_date' => Carbon::now(),
                    'sales_batch_transaction_date' => Carbon::now(),
                    'postedby' => Auth()->user()->idnumber,
                    'report_date' => Carbon::now()->format('Y-m-d'),
                ]);
            }
           
            $this->Generate_Shift_Sales();
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv')->commit();
            
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
        }
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
        DB::connection('sqlsrv')->beginTransaction();
        try{

            $type = Request()->payload['type'] ?? '';
            $closingtransaction = OpenningAmount::where('id',$id)->first();
            $terminal = (new Terminal)->terminal_details();
            $sales_batch = (new SeriesNo())->get_sequence('SBN',$terminal->terminal_code);
            $sales_sequenceno = (new SeriesNo())->generate_series($sales_batch->seq_no, $sales_batch->digit);
        
            $cashonhand['cashonhand_add_uncollected_card_day'] = (float)Request()->payload['cashonhand_add_uncollected_card_day'] ?? '';
            $cashonhand['cashonhand_less_collected_card_amount'] = (float)Request()->payload['cashonhand_less_collected_card_amount'] ?? '';
            $cashonhand['cashonhand_net_collections_for_the_day'] = (float)Request()->payload['cashonhand_net_collections_for_the_day'] ?? '';
            $cashonhand['cashonhand_overage_shortages'] = (float)Request()->payload['cashonhand_overage_shortages'] ?? '';
            $cashonhand['cashonhand_total_cash_check_collection_amount'] = (float)Request()->payload['cashonhand_total_cash_check_collection_amount'] ?? '';
            $cashonhand['cashonhand_total_collection_amount'] = (float)Request()->payload['cashonhand_total_collection_amount'] ?? '';
            $cashonhand['cashonhand_total_tendered_amount'] = (float)Request()->payload['cashonhand_total_tendered_amount'] ?? '';
            // $cashonhand['cashonhand_system_total_cash_tendered_amount'] = (float)Request()->payload['cashonhand_system_total_cash_tendered_amount'] ?? '';
            // $cashonhand['cashonhand_system_total_cash_sales_amount'] = (float)Request()->payload['cashonhand_system_total_cash_sales_amount'] ?? '';
            // $cashonhand['cashonhand_system_total_cash_changed_amount'] = (float)Request()->payload['cashonhand_system_total_cash_changed_amount'] ?? '';
            $cashonhand['terminal_id'] =(new Terminal)->terminal_details()->id;
            $cashonhand['cashonhand_closing_transaction'] = Carbon::now();
            $cashonhand['report_date'] =Carbon::now()->format('Y-m-d');
            $cashonhand['updatedBy'] =Auth()->user()->idnumber;
         

            $closingtransaction->update($cashonhand);
            $closingdetails = [];
            $totalamount = 0;
            foreach(Request()->openingitems as $row){
                $totalamount += (float)$row['denomination_amount'] * $row['denomination_qty'];
                if($row['id'] == '0'){
                    $closingdetails['denomination_1000'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '1'){
                    $closingdetails['denomination_500'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '2'){
                    $closingdetails['denomination_200'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '3'){
                    $closingdetails['denomination_100'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '4'){
                    $closingdetails['denomination_50'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '5'){
                    $closingdetails['denomination_20'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '6'){
                    $closingdetails['denomination_10'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '7'){
                    $closingdetails['denomination_5'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '8'){
                    $closingdetails['denomination_1'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '9'){
                    $closingdetails['denomination_dot25'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '10'){
                    $closingdetails['denomination_dot15'] = (float)$row['denomination_qty'];
                }
            }
            $closingdetails['denomination_total'] = (float)Request()->payload['totalamountcash'] ?? '';
            $closingdetails['denomination_checks_total_amount'] = (float)Request()->payload['totalchecks'] ?? '';
            $closingtransaction->cashonhand_details()->where('cashonhand_id',$id)->update($closingdetails);
         
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv')->commit();
            
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
        }
    }

    
    public function closing_transaction(Request $request, $id)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try{
            $from = Carbon::now()->format('Y-m-d');
            $to = Carbon::now()->format('Y-m-d').' 23:52';
            $closingtransaction = OpenningAmount::where('id',$id)->first();
            $terminal = (new Terminal)->terminal_details();
            $cashonhand['cashonhand_total_collection_amount'] = (float)Request()->payload['closed_amount'] ?? '';
            $cashonhand['terminal_id'] =(new Terminal)->terminal_details()->id;
            $cashonhand['cashonhand_closing_transaction'] = Carbon::now();
            $cashonhand['report_date'] =Carbon::now()->format('Y-m-d');
            $cashonhand['updatedBy'] =Auth()->user()->idnumber;
          
            DB::connection('sqlsrv_pos')->table('payments')->whereBetween('created_at',[$from,$to])->where('user_id',Auth()->user()->idnumber)->where('terminal_id',Auth()->user()->terminal_id)->where('shift_id',Auth()->user()->shift)->whereNull('report_date')->update([
                'report_date'=>Carbon::now()->format('Y-m-d')
            ]);
            $closingtransaction->update($cashonhand);
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv')->commit();
            
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status'=>$e->getMessage()], 200);
        }
    }

    public function posting_transaction(Request $request, $id)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try{

            $type = Request()->payload['type'] ?? '';
            $closingtransaction = OpenningAmount::where('id',$id)->first();
            $terminal = (new Terminal)->terminal_details();
            $sales_batch = (new SeriesNo())->get_sequence('SBN',$terminal->terminal_code);
            $sales_sequenceno = (new SeriesNo())->generate_series($sales_batch->seq_no, $sales_batch->digit);
           
          
            $cashonhand['isposted'] = '1';
            $cashonhand['sales_batch_number'] = $sales_sequenceno;
            $cashonhand['sales_batch_transaction_date'] = Carbon::now();
            $cashonhand['postedby'] = Auth()->user()->idnumber;
            $cashonhand['posted_date'] = Carbon::now();

            $closingtransaction->update($cashonhand);
           
            $sales_seriesno = $sales_batch->seq_no + 1;
            $sales_batch->update([
                'seq_no'=>$sales_seriesno,
                'recent_generated'=>$sales_sequenceno,
            ]);
            $from = Carbon::now()->format('Y-m-d');
            $to = Carbon::now()->format('Y-m-d').' 23:52';
            
            Payments::where('createdBy', Auth()->user()->idnumber)->where('shift_id',Auth()->user()->shift)->whereNull('sales_batch_number')->whereBetween('payment_date',[$from,$to])->update([
                'isposted' => '1',
                'sales_batch_number' => $sales_sequenceno,
                'sales_batch_transaction_date' => Carbon::now(),
                'postedby' => Auth()->user()->idnumber,
                'posted_date' => Carbon::now(),
            ]);
        
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv')->commit();
            
            return response()->json(["message" =>  'Record successfully saved','status'=>'200'], 200);
       
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv')->rollback();
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
