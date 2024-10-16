<?php

namespace App\Http\Controllers\POS\v1;

use DB;
use Carbon\Carbon;
use App\Models\POS\Orders;
use Illuminate\Http\Request;
use App\Models\POS\OrderItems;
use App\Helpers\RecomputePrice;
use App\Models\POS\POSSettings;
use App\Helpers\BatchTransactionLog;
use App\Http\Controllers\Controller;
use App\Models\POS\vwWarehouseItems;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\POS\v1\CustomerOrdersModel;
use App\Helpers\PosSearchFilter\UserDetails;
use App\Models\BuildFile\FmsTransactionCode;
use App\Models\MMIS\inventory\InventoryTransaction;
use App\Models\MMIS\inventory\ItemBatchModelMaster;

class NewCustomerOrderController extends Controller
{

    public function getcustomerorders(Request $request)
    {
        try {
            $terminal = (new Terminal())->terminal_details();
            $data = Orders::query();
            if (Request()->keyword) {
                $data->where('pick_list_number', 'LIKE', '%' . Request()->keyword . '%');
            }
            if (Request()->status) {
                $data->where('order_status_id', Request()->status);
            }
            if (Auth()->user()->role->name == 'Pharmacist Assistant') {
                $data->where('terminal_id', $terminal->id);
            }
            if (Auth()->user()->role->name == 'Pharmacist Cashier') {
                $data->where('take_order_terminal_id', $terminal->id);
            }

            $date = Carbon::now()->format('Y-m-d');
            $data->where('shift', Auth()->user()->shift);
            $data->whereDate('order_date', $date);
            $data->orderBy('id', 'desc');
            $page = Request()->per_page ? Request()->per_page : '-1';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::connection('sqlsrv_mmis')->beginTransaction();
            DB::connection('sqlsrv_pos')->beginTransaction();
            try {
                $checkorder = Orders::where('pick_list_number', $request->customer['pick_list_number'])->exists();
                $terminal = (new Terminal())->terminal_details();
                if (!$checkorder) {
                    $sequenceno = (new SeriesNo())->get_sequence('PPLN', 'Terminal-01');
                    $generatesequence = (new SeriesNo())->generate_series($sequenceno->seq_no, $sequenceno->digit);
                    if ($sequenceno->isSystem == '0' || $sequenceno->isSystem == '') {
                        $generatesequence = (new SeriesNo())->generate_series($sequenceno->manual_seq_no, $sequenceno->digit);
                    }
                } else {
                    $generatesequence = $request->customer['pick_list_number'];
                }
                $orders = Orders::updateOrCreate(
                    ['pick_list_number' => $generatesequence],
                    [
                        'branch_id' => Auth()->user()->branch_id,
                        'warehouse_id' => Auth()->user()->warehouse_id,
                        'customer_id' => $request->customer['id'] ?? '',
                        'pick_list_number' => $generatesequence,
                        'order_date' => Carbon::now(),
                        'order_total_line_item_ordered' => $request->payload['total_items'],
                        'order_vatable_sales_amount' => $request->payload['vatable_sales'],
                        'order_vatexempt_sales_amount' => $request->payload['vat_exempt_sales'],
                        'order_zero_rated_sales_amount' => $request->payload['zero_rated_sales'],
                        'order_vat_amount' => $request->payload['vat_amount_sales'],
                        'order_total_sales_vat_incl_amount' => $request->payload['total_sales_vat_include'],
                        'order_less_vat_amount' => $request->payload['less_vat'],
                        'order_vat_net_amount' => $request->payload['amount_net_vat'],
                        'order_senior_citizen_amount' => $request->payload['less_discount'],
                        'order_due_amount' => $request->payload['amount_due'],
                        'order_add_vat_amount' => $request->payload['add_vat'],
                        'order_total_payment_amount' => $request->payload['total_amount'],
                        'order_other_discount_amount' => $request->payload['total_discount'],
                        'pa_userid' => Auth()->user()->idnumber,
                        'cashier_user_id' => 0,
                        'checker_userid' => 0,
                        'terminal_id' => $terminal->id,
                        'take_order_terminal_id' => $terminal->terminal_Id,
                        'order_status_id' => $request->customer['paymentstatus'] ?? '7',
                        'createdBy' => Auth()->user()->idnumber,
                        'shift' => Auth()->user()->shift
                    ]
                );

                // $transaction = FmsTransactionCode::where('transaction_code', 'PY')->where('isActive', 1)->first();
                if (count($request->items) > 0) {
                    $orderid = null;
                    foreach ($request->items as $row) {
                        $orderid = $row['order_id'];
                        $pricein = (float)$row['item_Selling_Price_In'];
                        $priceout = (float)$row['item_Selling_Price_Out'];

                        $specialdiscount = ($pricein - $priceout) * $row['qty'];
                        $subtotal =   ($pricein * $row['qty']) - $specialdiscount;

                        if ($request->customer['customer_type'] == 'regular') {
                            $price = (float)$row['price'];
                            // $price = (float)$row['item_Selling_Price_Out'];
                            // $subtotal = ($priceout * $row['qty']) - $specialdiscount;
                            $discount = $row['discount'];
                            $totalamount = (float)$row['totalamount'];
                        } else {
                            $total = (float)$row['price'] * (float)$row['qty'];
                            $vat = (float)$row['vat_amount'] * (float)$row['qty'];
                            $compute_total_minus_vat = $total - $vat;
                            $discount = ((float)($total - $vat) * 0.20);
                            $totalamount = $compute_total_minus_vat - $discount;
                        }

                        $orders->order_items()->updateOrCreate(
                            [
                                'order_id' => $row['order_id'],
                                'order_item_id' => $row['id'],
                            ],
                            [
                                'order_item_id' => $row['id'],
                                'order_item_qty' => $row['qty'],
                                'order_item_charge_price' => round($pricein, 4),
                                'order_item_cash_price' => round($priceout, 4),
                                'order_item_price' => round((float)$row['price'], 4),
                                'order_item_vat_rate' => round((float)$row['vat_rate'], 4),
                                'order_item_vat_amount' => round((float)$row['vat_amount'], 4),
                                'order_item_sepcial_discount' => 0,
                                'order_item_discount_amount' => round((float)$discount, 4),
                                'order_item_total_amount' => round((float)$totalamount, 4),
                                'order_item_batchno' => $row['item_batch'],
                                'order_discount_type' => $row['discounttype'],
                                'isReturned' => '0',
                                'isDeleted' => '0',
                                'status' => '0',
                                'createdBy' => auth()->user()->idnumber,
                            ]
                        );
                        $batch = ItemBatchModelMaster::where("id", $row['item_batch'])->first();

                        (new BatchTransactionLog())->batchTransactionLogs($row['id'], $orders['pick_list_number']);
                        $warehouse = Warehouseitems::where("item_Id", $row['id'])->where('warehouse_Id', Auth()->user()->warehouse_id)->where('branch_id', Auth()->user()->branch_id)->first();

                        $isConsumed = '0';
                        $usedqty = (int)$batch->item_Qty_Used + $row['qty'];
                        if ($usedqty >= $batch->item_Qty) {
                            $isConsumed = '1';
                        }
                        $warehouse->update([
                            'item_OnHand' => (int)$warehouse->item_OnHand - (int)$row['qty']
                        ]);

                        $batch->update([
                            'item_Qty_Used' =>  (int)$batch->item_Qty_Used + (int)$row['qty'],
                            'isConsumed' =>  $isConsumed
                        ]);


                        // (new RecomputePrice())->compute(Auth()->user()->warehouse_id,$row['item_batch'],$row['id'],'out');
                    }

                    // Get the list of item IDs to be marked as deleted
                    $itemsToDelete = collect($request->items)->pluck('id');
                    // Update the status of items not in the $itemsToDelete list
                    $orders->order_items()
                        ->where('order_id', $orderid) // Include the order_id to scope the deletion
                        ->whereNotIn('order_item_id', $itemsToDelete)
                        ->update(['isDeleted' => '1']);
                }
                if (!$checkorder) {
                    if ($sequenceno->isSystem == '0' || $sequenceno->isSystem == '') {
                        $sequenceno->update([
                            'manual_seq_no' => (int)$sequenceno->manual_seq_no + 1,
                            'manual_recent_generated' => $generatesequence,
                        ]);
                    } else {
                        $sequenceno->update([
                            'seq_no' => (int)$sequenceno->seq_no + 1,
                            'recent_generated' => $generatesequence,
                        ]);
                    }
                }

                DB::connection('sqlsrv_pos')->commit();
                DB::connection('sqlsrv_mmis')->commit();
                $picklist = $this->print_picklist($orders->id);
                $pa_userid = Auth()->user()->idnumber;
                return response()->json(["message" => 'Record successfully saved', 'status' => '200', 'orders' => $picklist, 'picklistno' => $generatesequence], 200);
            } catch (\Exception $e) {
                DB::connection('sqlsrv_pos')->rollback();
                DB::connection('sqlsrv_mmis')->rollback();
                return response()->json(["message" => 'error', 'status' => $e->getMessage()], 200);
            }
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
    public function print_picklist($orderid)
    {
        if ($orderid) {
            $data['user'] = (new UserDetails())->userdetails(Auth()->user()->idnumber);
            $data['possetting']  = POSSettings::with('bir_settings')->where('isActive', '1')->first();
            $data['orders_picklist']  = Orders::where('id', $orderid)->first();
            return $data;
        }
    }
    public function cancelorder(Request $request)
    {
        try {
            DB::connection('sqlsrv_mmis')->beginTransaction();
            DB::connection('sqlsrv_pos')->beginTransaction();
            try {
                Orders::where('id', Request()->order_id)->update(
                    [
                        'order_status_id' => 8
                    ]
                );
                $orders = OrderItems::where('order_id', Request()->order_id)->get();
                foreach ($orders as $row) {
                    $warehouse = Warehouseitems::where("item_Id", $row['order_item_id'])->where('warehouse_Id', Auth()->user()->warehouse_id)->where('branch_id', Auth()->user()->branch_id)->first();
                    $batch = ItemBatchModelMaster::where("id", $row['order_item_batchno'])->first();

                    $isConsumed = '0';
                    $usedqty = (int)$batch->item_Qty_Used - $row['order_item_qty'];
                    if ($usedqty >= $batch->item_Qty) {
                        $isConsumed = '1';
                    }
                    $warehouse->update([
                        'item_OnHand' => (int)$warehouse->item_OnHand + (int)$row['order_item_qty']
                    ]);
                    $batch->update([
                        'item_Qty_Used' =>  (int)$batch->item_Qty_Used - (int)$row['order_item_qty'],
                        'isConsumed' =>  $isConsumed
                    ]);
                    // (new RecomputePrice())->compute(Auth()->user()->warehouse_id,'',$row['order_item_id'],'out');
                }

                DB::connection('sqlsrv_pos')->commit();
                DB::connection('sqlsrv_mmis')->commit();
                return response()->json(["message" => 'Record successfully saved', 'status' => '200'], 200);
            } catch (\Exception $e) {
                DB::connection('sqlsrv_pos')->rollback();
                DB::connection('sqlsrv_mmis')->rollback();
                return response()->json(["message" => 'error', 'status' => $e->getMessage()], 200);
            }
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 200);
        }
    }
}
