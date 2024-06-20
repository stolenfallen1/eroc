<?php

namespace App\Http\Controllers\MMIS;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\MMIS\TestModel;
use App\Models\BuildFile\Vendors;
use App\Models\Approver\InvStatus;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\BuildFile\SystemSequence;
use App\Models\MMIS\inventory\Consignment;
use App\Http\Requests\Procurement\PRRequest;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\inventory\ConsignmentItem;
use App\Models\MMIS\inventory\ConsignmentItems;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Helpers\SearchFilter\inventory\Consignments;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Models\MMIS\procurement\PurchaseRequestDetails;
use App\Models\MMIS\procurement\PurchaseRequestAttachment;
use App\Helpers\SearchFilter\Procurements\PurchaseRequests;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        // return TestModel::get();
        return (new PurchaseRequests)->searchable();
    }

    public function restorePR(Request $request, PurchaseRequest $purchase_request){

        // DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            
            // DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
        } catch (Exception $e) {
            // DB::connection('sqlsrv')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
        }
        $purchase_request->update([

        ]);
    }

    public function show($id)
    {
        $role = Auth::user()->role->name;
        return PurchaseRequest::with(['warehouse', 'status', 'category', 'subcategory', 'itemGroup', 'priority',
            'purchaseRequestAttachments', 'user', 'departmentApprovedBy', 'departmentDeclinedBy', 'administratorApprovedBy',
            'purchaseRequestDetails'=>function($q) use($role){
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
                }else if(Request()->tab==10){
                    $q->with(['itemMaster', 'canvases', 'recommendedCanvas' => function($q){
                        $q->with('vendor', 'canvaser','comptroller', 'unit');
                    }, 'unit', 'PurchaseOrderDetails' => function($query1){
                        $query1->with('purchaseOrder.user', 'unit');
                    }]);
                }else if(Request()->tab==8){
                    $q->with(['itemMaster', 'canvases', 'recommendedCanvas' => function($q){
                        $q->with('vendor', 'canvaser', 'unit');
                    }, 'unit', 'PurchaseOrderDetails' => function($query1){
                        $query1->with('purchaseOrder.user', 'unit');
                    }])->where('is_submitted', true);
                }
                else{
                    $q->with('itemMaster', 'canvases', 'recommendedCanvas.vendor')->where(function($query){
                        $query->whereHas('canvases', function($query1){
                            // $query1->whereDoesntHave('purchaseRequestDetail', function($q1){
                            //     $q1->where('is_submitted', [true,false]);
                            // });
                        })->orWhereDoesntHave('canvases');
                    })->where(function($q2){
                        $q2->where('pr_Branch_Level1_ApprovedBy', '!=', NULL)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
                    });
                }
            }, 'purchaseOrder' => function($q){
                $q->with('user', 'comptroller', 'administrator', 'corporateAdmin', 'president', 
                'details.item', 'details.unit', 'details.purchaseRequestDetail.recommendedCanvas.vendor');
            }])->findOrFail($id);
    }

    public function store(Request $request)
    {

        $status = InvStatus::where('Status_description', 'like', '%pending%')->select('id')->first()->id;
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
            $ismed = NULL;
            if($request->invgroup_id == 2){
                $ismed = 1;
            }
            if(isset($request->isconsignments) && $request->isconsignments == 1){
                $ismed = 1;
            }
            $pr = PurchaseRequest::create([
                'branch_Id' => (int)$user->branch_id,
                'warehouse_Id' => (int)$user->warehouse_id,
                'pr_Justication' => $request->pr_Justication,
                'pr_Transaction_Date' => Carbon::now(),
                'pr_Transaction_Date_Required' => Carbon::parse($request->pr_Transaction_Date_Required),
                'pr_RequestedBy' => $user->idnumber,
                'pr_Priority_Id' => $request->pr_Priority_Id,
                'invgroup_id' => $request->invgroup_id,
                'item_Category_Id' => $request->item_Category_Id,
                'item_SubCategory_Id' => $request->item_SubCategory_Id ?? NULL,
                'pr_Document_Number' => $number,
                'pr_Document_Prefix' => $prefix ?? "",
                'pr_Document_Suffix' => $suffex ?? "",
                'pr_Status_Id' => $status ?? null,
                'isPerishable' => $request->isPerishable ?? 0,
                'isconsignment'=>isset($request->isconsignments) ? 1 : 0 ,
                'ismedicine'=>$ismed 
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
                    'item_ListCost' => $item['item_ListCost'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'item_Request_Qty' => $item['item_Request_Qty'],
                    'prepared_supplier_id' => $item['prepared_supplier_id'] ?? 0,
                    'lead_time' => $item['lead_time'] ?? 0,
                    'vat_rate' => $item['vat_rate'] ?? 0,
                    'vat_type' => $item['vat_type'] ?? 0,
                    'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                ]);

                if(isset($request->isconsignments) && $request->isconsignments == 1){
                    ConsignmentItem::create([
                        'pr_request_id' => $pr['id'],
                        'rr_id' => $request['consignmentid'],
                        'item_group_id' => $request->invgroup_id,
                        'category_id' =>$request->item_Category_Id,
                        'request_item_id' => $item['item_Id'],
                        'consignmen_item_id' => $item['item_Id'],
                        'consignment_qty' => $item['rr_Detail_Item_Qty_Received'],
                        'request_qty' => $item['item_Request_Qty'],
                        'createdby' => $user->idnumber,
                        'consignment_balance_qty' => $item['rr_Detail_Item_Qty_Received'] - $item['item_Request_Qty'],
                    ]);

                    $check = ConsignmentItems::where('rr_id', $request['consignmentid'])
                        ->where('rr_Detail_Item_Id', $item['item_Id'])
                        ->first();

                    if ($check) {
                        // Update the pr_item_qty
                            ConsignmentItems::where('rr_id', $request['consignmentid'])
                            ->where('rr_Detail_Item_Id', $item['item_Id'])
                            ->update([
                                'pr_item_qty' => $check->pr_item_qty + $item['item_Request_Qty'],
                            ]);
                            $check1 = ConsignmentItems::where('rr_id', $request['consignmentid'])
                            ->where('rr_Detail_Item_Id', $item['item_Id'])
                            ->first();
                            ConsignmentItems::where('rr_id', $request['consignmentid'])
                            ->where('rr_Detail_Item_Id', $item['item_Id'])
                            ->update([
                                'pr_back_qty' => $check1->rr_Detail_Item_Qty_Received - ($check->pr_item_qty + $item['item_Request_Qty']),
                            ]);
                        // Check if all items are received
                        $allItemsReceived = ConsignmentItems::where('rr_id', $request['consignmentid'])
                            ->where('pr_back_qty', '>', 0)
                            ->exists();

                        if (!$allItemsReceived) {
                            Consignment::where('id', $request['consignmentid'])->update([
                                'receivedstatus' => 1
                            ]);
                        }
                    }
                }
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
            return response()->json(["error" => $e->getMessage()], 200);
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
            'pr_Document_Suffix' => $request->pr_Document_Suffix
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
                    'prepared_supplier_id' => $item['prepared_supplier_id'] ?? 0,
                ]);
            }else{
                $pr->purchaseRequestDetails()->create([
                    'filepath' => $file[0] ?? null,
                    'filename' => $file[2] ?? null,
                    'item_Id' => $item['item_Id'],
                    'item_Request_Qty' => $item['item_Request_Qty'],
                    'prepared_supplier_id' => $item['prepared_supplier_id'] ?? 0,
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
        if(Auth::user()->role->name == 'department head' || Auth::user()->role->name == 'dietary head'){
            $this->approveByDepartmentHead($request);
        } else if(Auth::user()->role->name == 'administrator') {
            $this->approveByAdministrator($request);
        } 
        else if(Auth::user()->role->name == 'consultant') {
            return $this->approveByConsultant($request);
        }
        return response()->json(["message" => "success"], 200);
    }

    private function approveByConsultant($request){

       
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $items = isset($request->items) ? $request->items: $request->purchase_request_details;
            foreach ($items as $key => $item ) {
                $prd  = PurchaseRequestDetails::where('id', $item['id'])->first();
                // return Auth::user()->role->name;
                if(!Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant){
                    if($request->invgroup_id == 2){
                        $this->addPharmaCanvas($item);
                    }
                }
             
                if(Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant){
                    if($request->branch_Id != 1){
                        if(isset($item['isapproved']) && $item['isapproved'] == true){
                            $prd->update([
                                'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                                'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                                'item_Request_Department_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                                'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            ]);
                        } else{
                            $prd->update([
                                'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                                'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                            ]);
                        }
                        if(isset($item['isapproved']) && $item['isapproved'] == true){
                            $prd->update([
                                'pr_Branch_Level2_ApprovedBy' => Auth::user()->idnumber,
                                'pr_Branch_Level2_ApprovedDate' => Carbon::now(),
                                
                                'item_Branch_Level1_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                                'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                                'item_Branch_Level2_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                                'item_Branch_Level2_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                                // 'is_submitted' => 1,
                            ]);
                        } else{
                            $prd->update([
                                'pr_Branch_Level2_CancelledBy' => Auth::user()->idnumber,
                                'pr_Branch_Level2_CancelledDate' => Carbon::now(),
                            ]);
                        }
                    }else{
                        if(isset($item['isapproved']) && $item['isapproved'] == true){
                            $prd->update([
                                'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                                'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                                'item_Request_Department_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                                'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            ]);
                        } else{
                            $prd->update([
                                'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                                'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                            ]);
                        }
                    }
                }else{
                    if(isset($item['isapproved']) && $item['isapproved'] == true){
                        $prd->update([
                            'pr_Branch_Level2_ApprovedBy' => Auth::user()->idnumber,
                            'pr_Branch_Level2_ApprovedDate' => Carbon::now(),
                            'item_Branch_Level1_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                            'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            'item_Branch_Level2_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                            'item_Branch_Level2_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            'is_submitted' => 1,
                        ]);
                    } else{
                        $prd->update([
                            'pr_Branch_Level2_CancelledBy' => Auth::user()->idnumber,
                            'pr_Branch_Level2_CancelledDate' => Carbon::now(),
                        ]);
                    }
                }
               
            }
            if(Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant){
                $pr = PurchaseRequest::where('id', $request->id)->first();
                if($request->branch_Id != 1){
                    if($request->isapproved){
                        $pr->update([
                            'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                        ]);
                    }else{
                        $pr->update([
                            'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                            'pr_DepartmentHead_Cancelled_Remarks' => $request->remarks,
                            'pr_Status_Id' => 3
                        ]);
                    }
                  
                    if($request->isapproved){
                        $pr->update([
                            'pr_Branch_Level2_ApprovedBy' => Auth::user()->idnumber,
                            'pr_Branch_Level2_ApprovedDate' => Carbon::now(),
                            'pr_Status_Id' => 6
                        ]);
                    }else{
                        $pr->update([
                            'pr_Branch_Level2_CancelledBy' => Auth::user()->idnumber,
                            'pr_Branch_Level2_CancelledDate' => Carbon::now(),
                            'pr_Branch_Level2_Cancelled_Remarks' => $request->remarks,
                            'pr_Status_Id' => 3
                        ]);
                    }
                }else{
                    if($request->isapproved){
                        $pr->update([
                            'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                        ]);
                    }else{
                        $pr->update([
                            'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                            'pr_DepartmentHead_Cancelled_Remarks' => $request->remarks,
                            'pr_Status_Id' => 3
                        ]);
                    }
                }
               
            }else{
                $pr = PurchaseRequest::where('id', $request->id)->first();
                if($request->isapproved){
                    $pr->update([
                        'pr_Branch_Level2_ApprovedBy' => Auth::user()->idnumber,
                        'pr_Branch_Level2_ApprovedDate' => Carbon::now(),
                        'pr_Status_Id' => 6
                    ]);
                }else{
                    $pr->update([
                        'pr_Branch_Level2_CancelledBy' => Auth::user()->idnumber,
                        'pr_Branch_Level2_CancelledDate' => Carbon::now(),
                        'pr_Branch_Level2_Cancelled_Remarks' => $request->remarks,
                        'pr_Status_Id' => 3
                    ]);
                }
            }
           
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }

    private function addPharmaCanvas($item){
        $vendor = Vendors::findOrfail($item['prepared_supplier_id']);
        $sequence = SystemSequence::where(['isActive' => true, 'code' => 'CSN1'])->first();
        $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
        $prefix = $sequence->seq_prefix;
        $suffix = $sequence->seq_suffix;
        
        $discount_amount = 0;
        $vat_amount = 0;
        $total_amount = $item['item_ListCost'] * $item['item_Request_Qty'];
        if($item['vat_rate']){
            if($vendor->isVATInclusive == 0){
                $vat_amount = $total_amount * ($item['vat_rate'] / 100);
                $total_amount += $vat_amount;
            }else{
                $vat_amount = $total_amount * ($item['vat_rate'] / 100);
            }
        }
        if($item['discount']){
            $discount_amount = $total_amount * ($item['discount'] / 100);
        }

        CanvasMaster::updateOrCreate(
            [
                'pr_request_id' => Request()->id,
                'pr_request_details_id' =>  $item['id'],
                'canvas_Item_Id' => $item['item_Id'],
                'vendor_id' => $vendor->id,
                'canvas_Branch_Id' => Request()->branch_Id
            ],
            [
            'canvas_Document_Number' => $number,
            'canvas_Document_Prefix' => $prefix,
            'canvas_Document_Suffix' => $suffix,
            'canvas_Document_CanvassBy' => Request()->pr_RequestedBy,
            'canvas_Document_Transaction_Date' => Carbon::now(),
            'requested_date' => Carbon::now(),
            'canvas_Branch_Id' => Request()->branch_Id,
            'canvas_Warehouse_Group_Id' => Request()->warehouse['warehouse_Group_Id'],
            'canvas_Warehouse_Id' =>  Request()->warehouse_Id,
            'vendor_id' => $vendor->id,
            'pr_request_id' => Request()->id,
            'pr_request_details_id' => $item['id'],
            'canvas_Item_Id' => $item['item_Id'],
            'canvas_Item_Qty' => $item['item_Request_Qty'],
            'canvas_Item_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
            'canvas_item_amount' => $item['item_ListCost'],
            'canvas_item_total_amount' => $total_amount,
            'canvas_item_discount_percent' => $item['discount'],
            'canvas_item_discount_amount' => $discount_amount,
            'canvas_item_net_amount' => $total_amount - $discount_amount,
            'canvas_lead_time' => $item['lead_time'],
            // 'canvas_remarks' => $request->canvas_remarks,
            'currency_id' => 1,
            'canvas_item_vat_rate' => $item['vat_rate'],
            'canvas_item_vat_amount' => $vat_amount,
            // 'isFreeGoods' => $request->isFreeGoods,
            'isRecommended' => 1,
            // 'canvas_Level2_ApprovedBy' => Request()->pr_RequestedBy,
            // 'canvas_Level2_ApprovedDate' => Carbon::now(),
        ]);

        $sequence->update([
            'seq_no' => (int) $sequence->seq_no + 1,
            'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
        ]);
    }

    private function approveByDepartmentHead($request){
        $items = isset($request->items) ? $request->items: $request->purchase_request_details;
        foreach ($items as $key => $item ) {
            $prd  = PurchaseRequestDetails::where('id', $item['id'])->first();
            // return Auth::user()->role->name;
            if(isset($item['isapproved']) && $item['isapproved'] == true){
                $prd->update([
                    'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                    'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                    'item_Request_Department_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                    'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                ]);
            } else{
                $prd->update([
                    'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                    'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                ]);
            }
        }
        $pr = PurchaseRequest::where('id', $request->id)->first();
        if($request->isapproved){
            $pr->update([
                'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
            ]);
        }else{
            $pr->update([
                'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                'pr_DepartmentHead_Cancelled_Remarks' => $request->remarks,
                'pr_Status_Id' => 3
            ]);
        }
    }

    private function approveByAdministrator($request){
        $items = isset($request->items) ? $request->items: $request->purchase_request_details;
        foreach ($items as $key => $item ) {
            $prd  = PurchaseRequestDetails::where('id', $item['id'])->first();
            if(isset($item['isapproved']) && $item['isapproved'] == true){
                $prd->update([
                    'pr_Branch_Level1_ApprovedBy' => Auth::user()->idnumber,
                    'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                    'item_Branch_Level1_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                    'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                ]);
            } else{
                $prd->update([
                    'pr_Branch_Level1_CancelledBy' => Auth::user()->idnumber,
                    'pr_Branch_Level1_CancelledDate' => Carbon::now(),
                ]);
            }
        }
        $pr = PurchaseRequest::where('id', $request->id)->first();
        if($request->isapproved){
            $pr->update([
                'pr_Branch_Level1_ApprovedBy' => Auth::user()->idnumber,
                'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                'pr_Status_Id' => 6
            ]);
        }else{
            $pr->update([
                'pr_Branch_Level1_CancelledBy' => Auth::user()->idnumber,
                'pr_Branch_Level1_CancelledDate' => Carbon::now(),
                'pr_Branch_Level1_Cancelled_Remarks' => $request->remarks,
                'pr_Status_Id' => 3
            ]);
        }
    }

    public function voidPR($id, Request $request){
        return PurchaseRequest::findOrfail($id)->update([
            'isvoid' => 1, 
        ]);
    }
}
