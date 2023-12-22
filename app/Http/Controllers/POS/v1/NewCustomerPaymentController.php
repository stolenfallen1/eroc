<?php

namespace App\Http\Controllers\POS\v1;

use DB;
use Carbon\Carbon;

use App\Models\POS\Orders;
use App\Models\POS\Payments;
use Illuminate\Http\Request;

use App\Models\POS\OrderItems;
use App\Models\POS\POSSettings;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\POS\vwPaymentReceiptItems;
use App\Helpers\PosSearchFilter\UserDetails;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;

class NewCustomerPaymentController extends Controller
{
     public function index(Request $request){
        try {
            $data = Payments::query();
            $data->with('orders');
            if(Request()->keyword) {
                $data->where('sales_invoice_number', 'LIKE', '%' . Request()->keyword.'%');
            }
            $date = Carbon::now()->format('Y-m-d');
            if(Request()->date) {
                $date  = Request()->date;
            }
            $data->whereDate('payment_date',$date);
            if(Auth()->user()->role->name == 'Pharmacist Cashier') {
                $data->where('user_id', Auth()->user()->idnumber);
                $data->where('shift_id', Auth()->user()->shift);

            }

            $data->orderBy('id', 'desc');
            $page  = Request()->per_page ?? '-1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    
    public function store(Request $request)
    {
        DB::connection('sqlsrv_pos')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            $terminal = (new Terminal())->terminal_details();
            $or_sequenceno = (new SeriesNo())->get_sequence('PSI', $terminal->terminal_code);
            $tran_sequenceno = (new SeriesNo())->get_sequence('PTN', $terminal->terminal_code);
            $generate_or_series = (new SeriesNo())->generate_series($or_sequenceno->seq_no, $or_sequenceno->digit);
            $generate_trans_series = (new SeriesNo())->generate_series($tran_sequenceno->seq_no, $tran_sequenceno->digit);
            if($or_sequenceno->isSystem == '0') {
                $generate_or_series = (new SeriesNo())->generate_series($or_sequenceno->manual_seq_no, $or_sequenceno->digit);
            }
            $payload = Request()->payload;
            $payment = Payments::create([
                'order_id' => $payload['order_id'],
                'sales_invoice_number' => $generate_or_series,
                'payment_transaction_number' => $generate_trans_series,
                'payment_date' => Carbon::now(),
                'payment_method_id' => $payload['payment']['paymenttype'] ?? '',
                'payment_method_card_id' =>$payload['payment']['cardtype'] ?? '',
                'payment_approval_code' => $payload['payment']['approvalcode'] ?? '',
                'payment_vatable_sales_amount' => (float)$payload['vatable_sales'] ?? '',
                'payment_vatable_exempt_sales_amount' => (float)$payload['vat_exempt_sales'] ?? '',
                'payment_zero_rated_sales_amount' => (float)$payload['zero_rated_sales'] ?? '',
                'payment_vatable_amount' => (float)$payload['vat_amount_sales'] ?? '',
                'payment_total_sales_vat_incl_amount' => (float)$payload['total_sales_vat_include'] ?? '',
                'payment_amount_net_of_vat' => (float)$payload['amount_net_vat'] ?? '',
                'payment_less_vat_amount' => (float)$payload['less_vat'] ?? '',
                'payment_add_vat_amount' => (float)$payload['add_vat'] ?? '',
                'payment_less_discount_amount' => (float)$payload['less_discount'] ?? '',
                'payment_discount_amount' => (float)$payload['total_discount'] ?? '',
                'payment_amount_due' => (float)$payload['amount_due'] ?? '',
                'payment_received_amount' => (float)$payload['payment']['amounttendered'] ?? '',
                'payment_changed_amount' => (float)$payload['payment']['change'] ?? '',
                'payment_total_amount' => (float)$payload['payment']['amounttopay'] ?? '',
                'payment_refund_amount' => '0',
                'terminal_id' => $terminal->id,
                'user_id' => Auth()->user()->idnumber,
                'shift_id' => Auth()->user()->shift,
                'createdBy' => Auth()->user()->idnumber,
           ]);

            $payment->orders()->update([
               'order_status_id' => '9'
            ]);
            $orders = OrderItems::where('order_id', $payload['order_id'])->get();
            $transaction = FmsTransactionCode::where('transaction_code', 'PY')->where('isActive', 1)->first();
            foreach ($orders as $row) {
                $warehouse = Warehouseitems::where("item_Id",$row['order_item_id'])->where('warehouse_Id',Auth()->user()->warehouse_id)->where('branch_id',Auth()->user()->branch_id)->first();
                $batch = ItemBatchModelMaster::where("id", $row['order_item_batchno'])->first();
                
                // $isConsumed = '0';
                // $usedqty = (int)$batch->item_Qty_Used + $row['order_item_qty'];
                // if($usedqty >= $batch->item_Qty) {
                //     $isConsumed = '1';
                // }
                // $warehouse->update([
                //         'item_OnHand'=> (int)$warehouse->item_OnHand - (int)$row['order_item_qty']   
                // ]);
                
                // $batch->update([
                //     'item_Qty_Used'=>  (int)$batch->item_Qty_Used + (int)$row['order_item_qty'],
                //     'isConsumed'=>  $isConsumed 
                // ]);
                InventoryTransaction::create([
                    'branch_Id' => Auth()->user()->branch_id,
                    'warehouse_Group_Id' => '',
                    'warehouse_Id' => Auth()->user()->warehouse_id,
                    'transaction_Item_Id' =>  $row['order_item_id'],
                    'transaction_Date' => Carbon::now(),
                    'trasanction_Reference_Number' => $generate_trans_series,
                    'transaction_ORNumber' => $generate_or_series,
                    'transaction_Item_UnitofMeasurement_Id' => $batch->item_UnitofMeasurement_Id,
                    'transaction_Qty' => $row['order_item_qty'],
                    'transaction_Item_OnHand' => $warehouse->item_OnHand,
                    'transaction_Item_ListCost' => $row['order_item_total_amount'],
                    'transaction_UserID' =>  Auth()->user()->idnumber,
                    'createdBy' => Auth()->user()->idnumber,
                    'transaction_Acctg_TransType' =>  $transaction->transaction_code ?? '',
                ]);

            }

            if($or_sequenceno->isSystem == '0') {
                $or_sequenceno->update([
                  'manual_seq_no' => (int)$or_sequenceno->manual_seq_no + 1,
                  'manual_recent_generated' => $generate_or_series,
                ]);
            } else {
                $or_sequenceno->update([
                  'seq_no' => (int)$or_sequenceno->seq_no + 1,
                  'recent_generated' => $generate_or_series,
                ]);
            }

            $trans_seriesno = $tran_sequenceno->seq_no + 1;
            $tran_sequenceno->update([
                'seq_no' => $trans_seriesno,
                'recent_generated' => $generate_trans_series,
            ]);

            DB::connection('sqlsrv_pos')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            $series_setting = SystemSequence::where('code', 'PSI')->select('isSystem', 'isPos')->first();
            $receipt = $this->print_receipt($payload['order_id']);
            return response()->json(["message" =>  'Record successfully saved','receipt'=>$receipt,'status' => '200'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv_pos')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["message" => 'error','status' => $e->getMessage()], 200);
        }
    }

    public function print_receipt($orderid){
        if($orderid){
            $data['user'] = (new UserDetails())->userdetails(Auth()->user()->idnumber);
            $data['possetting']  = POSSettings::with('bir_settings')->where('isActive', '1')->first();
            $data['orders_receipt']  = Payments::with('orders','users')->where('order_id', $orderid)->first();
            return $data;
        }
    }
}
