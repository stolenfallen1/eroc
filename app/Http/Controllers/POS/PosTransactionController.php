<?php

namespace App\Http\Controllers\POS;

use DB;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\POS\Customers;
use App\Models\POS\OpenningAmount;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\POSSetting;
use App\Models\BuildFile\MscCardType;
use App\Helpers\PosSearchFilter\Items;
use App\Models\BuildFile\MscDebitCard;
use App\Models\BuildFile\MscCreditCard;
use App\Models\BuildFile\MscRefundType;
use App\Models\BuildFile\SystemSequence;
use App\Models\MMIS\inventory\ItemBatch;
use App\Helpers\PosSearchFilter\SeriesNo;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\BuildFile\MscPaymentMethod;
use App\Models\POS\ItemCategoriesMappings;
use App\Models\POS\ItemInventoryGroupMappings;
use App\Models\BuildFile\SystemCentralSequences;

class PosTransactionController extends Controller
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
        $data = ItemBatch::where('item_Id', Request()->id)->where('warehouse_id', Request()->departmentid)->where('branch_id', Request()->branchid)->where('isConsumed','0')->select('id', 'batch_Number', 'item_Expiry_Date', 'item_Qty','item_Qty_Used')->get();
        return response()->json(["data"=>$data,"message" => "success"], 200);
    }

    public function getMscbuild()
    {
        $category = ['5','6'];
        $data['listofcategory'] =  ItemCategoriesMappings::where('warehouse_id', (int)Auth()->user()->warehouse_id)->whereNotIn('id',$category)->select('id','name')->get();
        $data['paymentmethod'] =  MscPaymentMethod::where('isactive','1')->select('id','payment_description')->get();
       
        $terminal = (new Terminal())->terminal_details();
        $or_sequenceno = (new SeriesNo())->get_sequence('PSI', $terminal->terminal_code);
        $series_setting = SystemSequence::where('code', 'PSI')->select('isSystem', 'isPos')->first();

        $seriesno = 0;
        if($series_setting->isSystem == 1 && $series_setting->isPos == 0) {
            $seriesno = $or_sequenceno->recent_generated;
        } elseif($series_setting->isSystem == 0 && $series_setting->isPos == 1) {
            $seriesno = $or_sequenceno->manual_recent_generated;
        }
      
        $data['seriesno'] = $seriesno;
        $data['cardtype'] =  MscCardType::where('isactive','1')->select('id','description')->get();
        $data['walkindetails'] =  Customers::where('isactive','1')->where('id','4')->first();
        $data['refundtype'] =  MscRefundType::where('isactive','1')->get();
        $data['message'] = 'success';
        return response()->json($data, 200);
    }

    public function getrefundtype(Request $request)
    {
        
        if($request->type == '1'){
            $data['refundtype'] =  MscRefundType::where('isactive','1')->whereIn('id',['1','3'])->get();
        }
        if($request->type == '2'){
            $data['refundtype'] =  MscRefundType::where('isactive','1')->whereIn('id',['2','3'])->get();
        }
        $data['message'] = 'success';
        return response()->json($data, 200);
    }
    
    public function openingstatus()
    {  
        $data['openingstatus'] = OpenningAmount::whereNull('sales_batch_number')->where('shift_code',Auth()->user()->shift)->where('user_id',Auth()->user()->idnumber)->whereDate('cashonhand_beginning_transaction',Carbon::now())->count();
        $data['message'] = 'success';
        return response()->json($data, 200);
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

    public function getDepartmentUsers()
    {
      return response()->json(['users' => User::whereNotNull('terminal_id')->where('role_id',13)->where('warehouse_id', Auth()->user()->warehouse_id)->select('name','idnumber','terminal_id','shift','warehouse_id')->get()]);
    }
}
