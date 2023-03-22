<?php

namespace App\Http\Controllers\POS;

use DB;
use Carbon\Carbon;
use App\Models\POS\Orders;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\POSSetting;
use App\Helpers\PosSearchFilter\Items;
use App\Models\BuildFile\Genericnames;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\MMIS\inventory\ItemBatch;
use App\Models\BuildFile\Itembatchnumbermasters;
use Karmendra\LaravelAgentDetector\AgentDetector;

class PosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = (new Items())->searchable();
        $possetting = POSSetting::select('vat_rate', 'seniorcitizen_discount_rate', 'pwd_discount_rate')->first();
        // Get the hostname
        return response()->json(["data"=>$data,"settings"=>$possetting,"message" => "success"], 200);
    }

   
    public function getbatchno(Request $request)
    {
        $data = ItemBatch::where('item_Id', Request()->id)->where('warehouse_id', Request()->departmentid)->select('id', 'batch_Number', 'item_Expiry_Date', 'item_Qty')->get();
        return response()->json(["data"=>$data,"message" => "success"], 200);
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
    public function saveorders(Request $request)
    {
        $sequenceno = SystemSequence::where('seq_prefix', 'PPLN')->first();
        $generatesequence = str_pad($sequenceno->seq_no, $sequenceno->digit, "0", STR_PAD_LEFT);

        $orders = Orders::create([
          'customer_id'=>Request()->payload['customer_payload']['id'] ?? '',
          'pick_list_number'=>$generatesequence,
          'order_date'=> Carbon::now(),
          'order_total_line_item_ordered'=> count(Request()->payload['cart_items']),
          'order_total_gross_amount'=> Request()->payload['cart_footer']['totalsalesvatinclude'],
          'order_total_discount_rate'=> Request()->payload['cart_footer']['discountrate'],
          'order_total_discount_amount'=> Request()->payload['cart_footer']['lessdiscount'],
          'order_vat_rate'=> Request()->payload['cart_footer']['vatablerate'],
          'order_vat_amount'=> Request()->payload['cart_footer']['vatamount'],
          'order_total_net_amount'=> Request()->payload['cart_footer']['totalamount'],
          'user_id'=>Auth()->user()->id,
          'order_status_id'=>1,
          'createdBy'=>Auth()->user()->id,
        ]);

        foreach (Request()->payload['cart_items'] as $row) {
            $orders->order_items()->create([
                'order_item_id'=>$row['id'],
                'order_item_qty'=>$row['itemqty'],
                'order_item_price'=>$row['itemprice'],
                'order_item_vat_rate'=>$row['vatablerate'],
                'order_item_vat_amount'=>$row['itemisvatable'],
                'order_item_discount_rate'=>$row['Discountrate'],
                'order_item_discount_amount'=>$row['itemisallowdiscount'],
                'isReturned'=>'0',
                'createdBy'=>Auth()->user()->id,
            ]);
        }



        $seriesno = $sequenceno->seq_no + 1;
        SystemSequence::where('seq_prefix', 'PPLN')->update([
          'seq_no'=>$seriesno,
          'recent_generated'=>$generatesequence,
        ]);
    //    echo print_r(Request()->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\c  $c
     * @return \Illuminate\Http\Response
     */
    public function show(c $c)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\c  $c
     * @return \Illuminate\Http\Response
     */
    public function edit(c $c)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\c  $c
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, c $c)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\c  $c
     * @return \Illuminate\Http\Response
     */
    public function destroy(c $c)
    {
        //
    }
}
