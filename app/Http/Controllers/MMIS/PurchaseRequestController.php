<?php

namespace App\Http\Controllers\MMIS;

use App\Helpers\SearchFilter\Procurements\PurchaseRequests;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Approver\invStatus;
use App\Models\MMIS\procurement\PurchaseRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestController extends Controller
{
    public function index(){
       return (new PurchaseRequests)->searchable();
    }

    public function store(Request $request){
        
        // 'pr_Document_Number',
        // 'pr_Document_Prefix',
        // 'pr_Document_Suffix',
        // 'warehouse_Group_Id',
        $pr = PurchaseRequest::create([
            'branch_Id' => Auth::user()->branch_id,
            'warehouse_Id' => Auth::user()->warehouse_id,
            'pr_Justication' => $request->justication,
            'pr_Transaction_Date' => Carbon::now(),
            'pr_Transaction_Date_Required' => Carbon::parse($request->required_date),
            'pr_RequestedBy' => Auth::user()->id,
            'pr_Priority_Id' => $request->pr_Priority_Id,
            'pr_Status_Id' => invStatus::where('Status_description', 'like', '%pending%')->select('id')->first(),
        ]);

        foreach($request->attachments as $key => $attachment){
            $file = storeDocument($attachment, "procurements/attachments", $key);
            $pr->purchaseRequestAttachments()->create([
                'filepath' => $file[0],
                'filename' => $file[2]
            ]);
        }
        // return $request->items;

        foreach($request->items as $item){
            $filepath = null;
            if(isset($item['attachment']) || $item['attachment'] !=null ){
                $filepath = storeDocument($item['attachment'], "procurements/items")[0];
            }
            $pr->purchaseRequestDetails()->create([
                'filepath' => $filepath,
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
