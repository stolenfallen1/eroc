<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Approver\invStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\BuildFile\SystemSequence;
use App\Http\Requests\Procurement\PRRequest;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Models\MMIS\procurement\PurchaseRequestDetails;
use App\Models\MMIS\procurement\PurchaseRequestAttachment;
use App\Helpers\SearchFilter\Procurements\PurchaseRequests;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        return (new PurchaseRequests)->searchable();
    }

    public function show($id)
    {
        $role = Auth::user()->role->name;
        return PurchaseRequest::with(['warehouse', 'status', 'category', 'subcategory', 'itemGroup', 'priority',
            'purchaseRequestAttachments', 'user', 'purchaseRequestDetails'=>function($q) use($role){
                if(Request()->tab==6){
                    $q->with('itemMaster', 'canvases', 'recommendedCanvas.vendor')
                    ->where(function($query){
                        $query->whereHas('recommendedCanvas', function($query1){
                            $query1->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
                        });
                    })->where('is_submitted', true);
                }else if(Request()->tab==7){
                    $q->with('itemMaster', 'canvases', 'recommendedCanvas.vendor')->where(function($query){
                        $query->whereHas('recommendedCanvas', function($query1){
                            $query1->where('canvas_Level2_ApprovedBy', '!=', null)
                            ->orWhere('canvas_Level2_CancelledBy', '!=', null);
                        });
                    })->where('is_submitted', true);
                }else if(Request()->tab==9){
                    $q->with('itemMaster', 'canvases', 'recommendedCanvas.vendor')->where(function($query){
                        $query->whereHas('recommendedCanvas', function($query1){
                            $query1->where('canvas_Level2_ApprovedBy', '!=', null)
                            ->orWhere('canvas_Level2_CancelledBy', '!=', null);
                        });
                    })->whereDoesntHave('purchaseOrderDetails');
                }
                else{
                    $q->with('itemMaster', 'canvases', 'recommendedCanvas.vendor')->where(function($query){
                        $query->whereHas('canvases', function($query1){
                            $query1->whereDoesntHave('purchaseRequestDetail', function($q1){
                                $q1->where('is_submitted', true);
                            });
                        })->orWhereDoesntHave('canvases');
                    })->where('pr_Branch_Level1_ApprovedBy', '!=', NULL);
                }
            }])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $status = invStatus::where('Status_description', 'like', '%pending%')->select('id')->first()->id;
        $user = Auth::user();
        $sequence = SystemSequence::where('seq_description', 'like', '%Purchase Requisition Series Number%')
            ->where(['isActive' => true, 'branch_id' => $user->branch_id])->first();
        $number = $request->pr_Document_Number;
        $prefix = $request->pr_Document_Prefix;
        $suffex = $request->pr_Document_Suffix;
        if($sequence && $sequence->isSystem){
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffex = $sequence->seq_suffix;
        }

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {
            $pr = PurchaseRequest::create([
                'branch_Id' => (int)$user->branch_id,
                'warehouse_Id' => (int)$user->warehouse_id,
                'pr_Justication' => $request->pr_Justication,
                'pr_Transaction_Date' => Carbon::now(),
                'pr_Transaction_Date_Required' => Carbon::parse($request->pr_Transaction_Date_Required),
                'pr_RequestedBy' => $user->id,
                'pr_Priority_Id' => $request->pr_Priority_Id,
                'invgroup_id' => $request->invgroup_id,
                'item_Category_Id' => $request->item_Category_Id,
                'item_SubCategory_Id' => $request->item_SubCategory_Id ?? NULL,
                'pr_Document_Number' => $number,
                'pr_Document_Prefix' => $prefix ?? "",
                'pr_Document_Suffix' => $suffex ?? "",
                'pr_Status_Id' => $status ?? null,
                'isPersihable' => $request->isPersihable ?? 0,
            ]);
            if (isset($request->attachments) && $request->attachments != null && sizeof($request->attachments) > 0) {
                foreach ($request->attachments as $key => $attachment) {
                    $file = storeDocument($attachment, "procurements/attachments", $key);
                    $pr->purchaseRequestAttachments()->create([
                        'filepath' => $file[0],
                        'filename' => $file[2]
                    ]);
                }
            }
            // return $request->items;
    
            foreach ($request->items as $item) {
                $filepath = [];
                if (isset($item['attachment']) && $item['attachment'] != null) {
                    $filepath = storeDocument($item['attachment'], "procurements/items");
                }
                $pr->purchaseRequestDetails()->create([
                    'filepath' => $filepath[0] ?? null,
                    'filename' => $filepath[2] ?? null,
                    'item_Id' => $item['item_Id'],
                    'item_Request_Qty' => $item['item_Request_Qty'],
                    'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                ]);
            }

            $sequence->update([
                'seq_no' => (int) $sequence->seq_no + 1,
                'recent_generated' => generateCompleteSequence($prefix, $number, $suffex, "-"),
            ]);

            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(["message" => "success"], 200);
        } catch (\Exception  $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e], 200);
        }
        

    }

    public function update(Request $request, $id)
    {
        $pr = PurchaseRequest::with('purchaseRequestAttachments')->where('id', $id)->first();
        $pr->update([
            'pr_Justication' => $request->pr_Justication,
            'pr_Transaction_Date_Required' => Carbon::parse($request->pr_Transaction_Date_Required),
            'pr_Priority_Id' => $request->pr_Priority_Id,
            'invgroup_id' => $request->invgroup_id,
            'item_Category_Id' => $request->item_Category_Id,
            'item_SubCategory_Id' => $request->item_SubCategory_Id,
            'pr_Document_Number' => $request->pr_Document_Number,
            'pr_Document_Prefix' => $request->pr_Document_Prefix,
            'pr_Document_Suffix' => $request->pr_Document_Suffix,
        ]);

        if (isset($request->attachments) && $request->attachments != null && sizeof($request->attachments) > 0) {
            $isremove = false;
            foreach ($request->attachments as $key => $attachment) {
                if (!str_contains($attachment, 'object')) {
                    if (!$isremove) {
                        if(sizeof($pr->purchaseRequestAttachments)){
                            foreach ($pr->purchaseRequestAttachments as $attach) {
                                File::delete(public_path().$attach->filepath);
                            }
                            PurchaseRequestAttachment::where('pr_request_id', $pr->id)->delete();
                            $isremove = true;
                        }
                    }
                    $file = storeDocument($attachment, "procurements/attachments", $key);
                    $pr->purchaseRequestAttachments()->create([
                        'filepath' => $file[0],
                        'filename' => $file[2]
                    ]);
                }
            }
        }

        foreach ($request->items as $item) {
            $file = [];
            if (isset($item['attachment']) && $item['attachment'] != null) {
                if (!str_contains($item['attachment'], 'object')) {
                    $file = storeDocument($item['attachment'], "procurements/items");
                }
            }

            if(isset($item["id"])){
                $pr->purchaseRequestDetails()->where('id', $item['id'])->update([
                    'item_Id' => $item['item_Id'],
                    'item_Request_Qty' => $item['item_Request_Qty'],
                    'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                ]);
            }else{
                $pr->purchaseRequestDetails()->create([
                    'filepath' => $file[0] ?? null,
                    'filename' => $file[2] ?? null,
                    'item_Id' => $item['item_Id'],
                    'item_Request_Qty' => $item['item_Request_Qty'],
                    'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                ]);
            }
        }

        return response()->json(["message" => "success"], 200);
    }

    public function removeItem($id){
        return PurchaseRequestDetails::where('id', $id)->delete();
    }

    public function updateItemAttachment(Request $request, $id){
        $file = storeDocument($request['attachment'], "procurements/items");
        PurchaseRequestDetails::where('id', $id)->update([
            'filepath' => $file[0] ?? null,
            'filename' => $file[2] ?? null,
        ]);
        
    }

    public function destroy($id)
    {
        $pr = PurchaseRequest::with('purchaseRequestAttachments', 'purchaseRequestDetails')->where('id', $id)->first();
        foreach ($pr->purchaseRequestAttachments as $attachment) {
            File::delete(public_path().$attachment->filepath);
            $attachment->delete();
        }
        foreach ($pr->purchaseRequestDetails as $detail) {
            File::delete(public_path().$detail->filepath);
            $detail->delete();
        }
        $pr->delete();
        return response()->json(["message" => "success"], 200);
    }

    public function approveItems(Request $request){
        if(Auth::user()->role->name == 'department head'){
            $this->approveByDepartmentHead($request);
        } else if(Auth::user()->role->name == 'administrator') {
            $this->approveByAdministrator($request);
        }
        return response()->json(["message" => "success"], 200);
    }

    private function approveByDepartmentHead($request){
        foreach ($request->items as $key => $item ) {
            $prd  = PurchaseRequestDetails::where('id', $item['id'])->first();
            // return Auth::user()->role->name;
            if(isset($item['isapproved']) && $item['isapproved'] == true){
                $prd->update([
                    'pr_DepartmentHead_ApprovedBy' => Auth::user()->id,
                    'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                    'item_Request_Department_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                    'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                ]);
            } else{
                $prd->update([
                    'pr_DepartmentHead_CancelledBy' => Auth::user()->id,
                    'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                ]);
            }
        }
        $pr = PurchaseRequest::where('id', $request->id)->first();
        if($request->isapproved){
            $pr->update([
                'pr_DepartmentHead_ApprovedBy' => Auth::user()->id,
                'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
            ]);
        }else{
            $pr->update([
                'pr_DepartmentHead_CancelledBy' => Auth::user()->id,
                'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                'pr_DepartmentHead_Cancelled_Remarks' => $request->remarks,
                'pr_Status_Id' => 3
            ]);
        }
    }

    private function approveByAdministrator($request){
        foreach ($request->items as $key => $item ) {
            $prd  = PurchaseRequestDetails::where('id', $item['id'])->first();
            if(isset($item['isapproved']) && $item['isapproved'] == true){
                $prd->update([
                    'pr_Branch_Level1_ApprovedBy' => Auth::user()->id,
                    'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                    'item_Branch_Level1_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                    'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                ]);
            } else{
                $prd->update([
                    'pr_Branch_Level1_CancelledBy' => Auth::user()->id,
                    'pr_Branch_Level1_CancelledDate' => Carbon::now(),
                ]);
            }
        }
        $pr = PurchaseRequest::where('id', $request->id)->first();
        if($request->isapproved){
            $pr->update([
                'pr_Branch_Level1_ApprovedBy' => Auth::user()->id,
                'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                'pr_Status_Id' => 6
            ]);
        }else{
            $pr->update([
                'pr_Branch_Level1_CancelledBy' => Auth::user()->id,
                'pr_Branch_Level1_CancelledDate' => Carbon::now(),
                'pr_Branch_Level1_Cancelled_Remarks' => $request->remarks,
                'pr_Status_Id' => 3
            ]);
        }
    }
}
