<?php

namespace App\Http\Controllers\MMIS;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MMIS\procurement\PurchaseRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestController extends Controller
{
    public function index(){
    
    }

    public function store(Request $request){
        
        return Auth::user();
        $pr = PurchaseRequest::create([
            // 'pr_Document_Number',
            // 'pr_Document_Prefix',
            // 'pr_Document_Suffix',
            // 'warehouse_Group_Id',
            'branch_Id' => Auth::user()->branch_id,
            'warehouse_Id' => Auth::user()->warehouse_id,
            'pr_Justication' => $request->justication,
            'pr_Transaction_Date' => Carbon::now(),
            'pr_Transaction_Date_Required' => Carbon::parse($request->required_date),
            'pr_RequestedBy', Auth::user()->id,
            'pr_Priority_Id', 1,
        ]);
        foreach($request->attachments as $attachment){
            $pr->purchaseRequestAttachments->create([
                'filepath' => storeDocument($attachment, "/procurements/attachments")
            ]);
        }
        foreach($request->items as $item){
            $pr->purchaseRequestDetails->create([
                'filepath' => storeDocument($item['attachment'], "/procurements/items"),
                'item_Id' => $item['item_Id'],
                'item_Request_Qty' => $item['item_Request_Qty'],
                'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
            ]);
        }
        
        return response()->json(["message" => "success"], 200);
    }

    public function update(Request $request, $id){

    }

    public function destroy($id){

    }
}
