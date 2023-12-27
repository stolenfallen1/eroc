<?php

namespace App\Http\Controllers\POS\v1;

use DB;
use Carbon\Carbon;
use App\Models\POS\Payments;
use Illuminate\Http\Request;
use App\Models\POS\OpenningAmount;
use App\Http\Controllers\Controller;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Helpers\PosSearchFilter\Openingbalance;

class RegistryController extends Controller
{
    public function index()
    {
        $data['data'] = (new Openingbalance())->searchable();
        $data['message'] = 'success';
        return response()->json($data, 200);
    }

    public function store(Request $request)
    {


        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try {

            $terminal = (new Terminal())->terminal_details();
            $closingtransaction = OpenningAmount::where('id', Request()->payload['id'])->first();
            $closingtransaction->update([
                'cashonhand_add_uncollected_card_day' => (float)Request()->payload['cashonhand_add_uncollected_card_day'] ?? '',
                'cashonhand_less_collected_card_amount' => (float)Request()->payload['cashonhand_less_collected_card_amount'] ?? '',
                'cashonhand_net_collections_for_the_day' => (float)Request()->payload['cashonhand_net_collections_for_the_day'] ?? '',
                'cashonhand_overage_shortages' => (float)Request()->payload['cashonhand_overage_shortages'] ?? '',
                'cashonhand_total_cash_check_collection_amount' => (float)Request()->payload['cashonhand_total_cash_check_collection_amount'] ?? '',
                'cashonhand_total_collection_amount' => (float)Request()->payload['cashonhand_total_collection_amount'] ?? '',
                'cashonhand_total_tendered_amount' => (float)Request()->payload['cashonhand_total_tendered_amount'] ?? '',
            ]);
            $closingdetails = [];
            $totalamount = 0;
            foreach(Request()->openingitems as $row) {
                $totalamount += (float)$row['denomination_amount'] * $row['denomination_qty'];
                if($row['id'] == '0') {
                    $closingdetails['denomination_1000'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '1') {
                    $closingdetails['denomination_500'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '2') {
                    $closingdetails['denomination_200'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '3') {
                    $closingdetails['denomination_100'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '4') {
                    $closingdetails['denomination_50'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '5') {
                    $closingdetails['denomination_20'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '6') {
                    $closingdetails['denomination_10'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '7') {
                    $closingdetails['denomination_5'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '8') {
                    $closingdetails['denomination_1'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '9') {
                    $closingdetails['denomination_dot25'] = (float)$row['denomination_qty'];
                }
                if($row['id'] == '10') {
                    $closingdetails['denomination_dot15'] = (float)$row['denomination_qty'];
                }
            }
            $closingdetails['denomination_total'] = (float)Request()->payload['totalamountcash'] ?? '';
            $closingdetails['denomination_checks_total_amount'] = (float)Request()->payload['totalchecks'] ?? '';
            $closingtransaction->cashonhand_details()->where('cashonhand_id', Request()->payload['id'])->update($closingdetails);


            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv')->commit();

            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);
        }
    }

    public function postregistry()
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $terminal = (new Terminal())->terminal_details();
            $report_date = Carbon::now()->format('Y-m-d');
            $closingtransaction = OpenningAmount::where('id', Request()->id)->first();
            $closingtransaction->update([
                'isposted' => '1',
                'postedby' => Auth()->user()->idnumber,
                'updatedBy' => Auth()->user()->idnumber,
                'posted_date' => Carbon::now(),
            ]);
            Payments::where('createdBy', Auth()->user()->idnumber)->where('shift_id', Auth()->user()->shift)->whereDate('payment_date', $report_date)->update([
                'isposted' => '1',
                'posted_date' => Carbon::now(),
                'postedby' => Auth()->user()->idnumber,
            ]);
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);
        }
    }

    public function closedregistry()
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv')->beginTransaction();
        try {
            $terminal = (new Terminal())->terminal_details();
            $report_date = Carbon::now()->format('Y-m-d');
            $sales_batch = (new SeriesNo())->get_sequence('SBN', $terminal->terminal_code);
            $sales_sequenceno = (new SeriesNo())->generate_series($sales_batch->seq_no, $sales_batch->digit);
            $closingtransaction = OpenningAmount::where('id', Request()->id)->first();
            $closingtransaction->update([
             
                'sales_batch_number' => $sales_sequenceno,
                'cashonhand_closing_transaction' => Carbon::now(),
                'sales_batch_transaction_date' => Carbon::now(),
                'report_date' => $report_date,
            ]);
            
            $sales_seriesno = $sales_batch->seq_no + 1;
            $sales_batch->update([
                'seq_no' => $sales_seriesno,
                'recent_generated' => $sales_sequenceno,
            ]);

            Payments::where('createdBy', Auth()->user()->idnumber)->where('shift_id', Auth()->user()->shift)->whereDate('payment_date', $report_date)->update([
                'sales_batch_number' => $sales_sequenceno,
                'sales_batch_transaction_date' => Carbon::now(),
                'report_date' => $report_date,
            ]);

            $this->Generate_Shift_Sales();
            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv')->commit();
            return response()->json(["message" =>  'Record successfully saved','status' => '200'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);
        }
    }
    
    public function Generate_Shift_Sales()
    {
        $result = DB::connection('sqlsrv_pos')->update(
            "EXEC spGenerate_Shift_Sales ?, ?, ?,?,?",
            [
                Auth()->user()->branch_id,
                Auth()->user()->terminal_id,
                Auth()->user()->shift,
                Carbon::now()->format('m/d/Y'),
                Auth()->user()->idnumber,
        ]
        );
        return $result;
    }
}
