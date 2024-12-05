<?php

namespace App\Http\Controllers\MMIS;

use Exception;
use Carbon\Carbon;
use App\Helpers\ParentRole;
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
use App\Models\MMIS\inventory\ConsignmentItems;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\VwPrTransactionLog;
use App\Helpers\SearchFilter\inventory\Consignments;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Models\MMIS\inventory\PurchaseOrderConsignment;
use App\Models\MMIS\procurement\PurchaseRequestDetails;
use App\Models\MMIS\procurement\PurchaseRequestAttachment;
use App\Helpers\SearchFilter\Procurements\PurchaseRequests;
use App\Models\MMIS\inventory\PurchaseOrderConsignmentItem;

class PurchaseRequestController extends Controller
{
    protected $authUser;
    protected $role;

    public function __construct()
    {
        // $this->model = DB::connection('sqlsrv_mmis')->table('purchaseRequestMaster');
        $this->authUser = auth()->user();
        $this->role = new ParentRole();
    }
    public function index()
    {
        // return TestModel::get();
        return (new PurchaseRequests)->searchable();
    }

    public function restorePR(Request $request, PurchaseRequest $purchase_request)
    {

        // DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {

            // DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
        } catch (Exception $e) {
            // DB::connection('sqlsrv')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
        }
        $purchase_request->update([]);
    }

    public function allPR()
    {
        $requestingDepartmet = Request()->department_id;
        $allpr = VwPrTransactionLog::where('warehouse_Id', $requestingDepartmet)->get();
        return response()->json($allpr, 200);
    }
    public function show($id)
    {
        $role = Auth::user()->role->name;
        return PurchaseRequest::with([
            'warehouse',
            'status',
            'category',
            'subcategory',
            'itemGroup',
            'priority',
            'purchaseRequestAttachments',
            'user',
            'departmentApprovedBy',
            'departmentDeclinedBy',
            'administratorApprovedBy',
            'purchaseRequestDetails' => function ($q) use ($id) {
                if (Request()->tab == 6) {
                    $q->with('itemMaster', 'canvases', 'recommendedCanvas.vendor')
                        ->where(function ($query) {
                            $query->whereHas('recommendedCanvas', function ($query1) {
                                $query1->where(['canvas_Level2_ApprovedBy' => null, 'canvas_Level2_CancelledBy' => null]);
                            });
                        })->where('is_submitted', true);
                } else if (Request()->tab == 7) {
                    $q->with('depApprovedBy', 'adminApprovedBy', 'conApprovedBy', 'itemMaster', 'canvases', 'recommendedCanvas.vendor')->where(function ($query) {
                        $query->whereHas('recommendedCanvas', function ($query1) {
                            $query1->where('canvas_Level2_ApprovedBy', '!=', null)
                                ->orWhere('canvas_Level2_CancelledBy', '!=', null);
                        });
                    })->where('is_submitted', true);
                } else if (Request()->tab == 9) {
                    $q->with('depApprovedBy', 'adminApprovedBy', 'conApprovedBy', 'itemMaster', 'canvases', 'recommendedCanvas.vendor')->where(function ($query) {
                        $query->whereHas('recommendedCanvas', function ($query1) {
                            $query1->where('canvas_Level2_ApprovedBy', '!=', null)
                                ->orWhere('canvas_Level2_CancelledBy', '!=', null);
                        });
                    })->whereDoesntHave('purchaseOrderDetails');
                } else if (Request()->tab == 10) {
                    $q->with(['depApprovedBy', 'preparedSupplier', 'adminApprovedBy', 'conApprovedBy', 'itemMaster', 'canvases', 'recommendedCanvas' => function ($q) {
                        $q->with('vendor', 'canvaser', 'comptroller', 'unit');
                    }, 'unit', 'PurchaseOrderDetails' => function ($query1) {
                        $query1->with('purchaseOrder.user', 'unit', 'purchaseOrder.vendor');
                    }]);
                } else if (Request()->tab == 8) {
                    $q->with(['itemMaster', 'canvases', 'recommendedCanvas' => function ($q) {
                        $q->with('vendor', 'canvaser', 'unit');
                    }, 'unit', 'PurchaseOrderDetails' => function ($query1) {
                        $query1->with('purchaseOrder.user', 'unit');
                    }])->where('is_submitted', true);
                } else {

                    if (PurchaseRequest::where('id', $id)->where('ismedicine', 1)->exists() || PurchaseRequest::where('id', $id)->where('isdietary', 1)->exists()) {
                        $q->with('itemMaster', 'canvases', 'recommendedCanvas.vendor')
                            ->where(function ($query) {
                                $query->whereHas('canvases', function ($query1) {
                                    // Keeping the comment if `ismedicine` is 1
                                    // $query1->whereDoesntHave('purchaseRequestDetail', function ($q1) {
                                    //     $q1->where('is_submitted', [true, false]);
                                    // });
                                })
                                    ->orWhereDoesntHave('canvases');
                            });
                    } else {
                        $q->with('itemMaster', 'itemMaster.wareHouseItem', 'canvases', 'recommendedCanvas.vendor')->where(function ($query) {
                            $query->whereHas('canvases', function ($query1) {
                                $query1->whereDoesntHave('purchaseRequestDetail', function ($q1) {
                                    $q1->where('is_submitted', true);
                                });
                            })->orWhereDoesntHave('canvases');
                        })
                            ->where(function ($q2) {
                                $q2->where('pr_Branch_Level1_ApprovedBy', '!=', NULL)->orWhere('pr_Branch_Level2_ApprovedBy', '!=', null);
                            });
                    }
                }
            },
            'purchaseOrder' => function ($q) {
                $q->with(
                    'user',
                    'comptroller',
                    'administrator',
                    'corporateAdmin',
                    'president',
                    'details.item',
                    'details.unit',
                    'details.purchaseRequestDetail.recommendedCanvas.vendor',
                    'vendor'
                );
            }
        ])->findOrFail($id);
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
        if ($sequence && $sequence->isSystem) {
            $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
            $prefix = $sequence->seq_prefix;
            $suffex = $sequence->seq_suffix;
        }

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();

        try {
            $ismed = NULL;
            if ($this->role->pharmacy_warehouse()) {
                $ismed = 1;
            }
            if ($request->isconsignments && $request->isconsignments == 1) {
                $ismed = 1;
            }
            $isdiet = NULL;
            if ($this->role->isdietary()  || $this->role->isdietaryhead()) {
                $isdiet = 1;
            }
            $pr = PurchaseRequest::updateOrCreate(
                [
                    'pr_Document_Number' => $number,
                    'branch_Id' => (int)$user->branch_id
                ],
                [
                    'branch_Id' => (int)$user->branch_id,
                    'warehouse_Id' => (int)$request->department_id ?? $user->warehouse_id,
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
                    // 'vendor_Id'=> isset($request->prepared_supplier_id) ? $request->prepared_supplier_id : '',
                    'pr_Status_Id' => $status ?? null,
                    'isPerishable' => $request->isPerishable ?? 0,
                    'isconsignment' => $request->isconsignments,
                    'ismedicine' => $ismed,
                    'isdietary' => $isdiet
                ]
            );
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
            if ($request->isconsignments && $request->isconsignments == 1) {

                $po_consignment = PurchaseOrderConsignment::updateOrCreate(
                    [
                        'pr_request_id' => $pr['id'],
                        'rr_id' => $request['consignmentid'],
                    ],
                    [
                        'pr_request_id' => $pr['id'],
                        'rr_id' => $request['consignmentid'],
                        'item_group_id' => $request->invgroup_id,
                        'category_id' => $request->item_Category_Id,
                        'vendor_id' => $request['Vendor_Id'] ?? 0,
                        'createdby' => $user->idnumber,
                    ]
                );
            }
            foreach ($request->items as $item) {
                $filepath = [];
                if (isset($item['attachment']) && $item['attachment'] != null) {
                    $filepath = storeDocument($item['attachment'], "procurements/items");
                }

                $pr->purchaseRequestDetails()->updateOrCreate(
                    [
                        'pr_request_id' => $pr['id'],
                        'item_Id' => $item['item_Id'],
                    ],
                    [
                        'filepath' => $filepath[0] ?? null,
                        'filename' => $filepath[2] ?? null,
                        'item_Id' => $item['item_Id'],
                        'item_ListCost' => $item['item_ListCost'] ?? 0,
                        'discount' => $item['discount'] ?? 0,
                        'item_Request_Qty' => $item['item_Request_Qty'],
                        'prepared_supplier_id' => isset($item['prepared_supplier_id']) ? $item['prepared_supplier_id'] : 0,
                        'recommended_supplier_id' => isset($item['prepared_supplier_id']) ? $item['prepared_supplier_id'] : 0,
                        'lead_time' => $item['lead_time'] ?? 0,
                        'vat_rate' => $item['vat_rate'] ?? 0,
                        'vat_type' => $item['vat_type'] ?? 0,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'vat_amount' => $item['vat_amount'] ?? 0,
                        'total_amount' => $item['total_amount'] ?? 0,
                        'total_net' => $item['total_net'] ?? 0,
                        'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                        'ismedicine' => $ismed,
                        'isdietary' => $isdiet
                    ]
                );

                if ($this->role->pharmacy_warehouse() || $this->role->isdietary()) {
                    $details =  $pr->purchaseRequestDetails()->where('pr_request_id', $pr['id'])->where('item_Id', $item['item_Id'])->first();
                    $item['id'] = $details->id;
                    $item['vat_rate'] = $details->vat_rate;
                    $item['vat_type'] = $details->vat_type;
                    $item['discount'] = $details->discount;
                    $item['discount_amount'] = $details->discount_amount;
                    $item['vat_rate'] = $details->vat_rate;
                    $item['vat_amount'] = $details->vat_amount;
                    $item['total_amount'] = $details->total_amount;
                    $item['total_net'] = $details->total_net;
                    $item['lead_time'] = $details->lead_time;
                    $item['pr_id'] = $pr['id'];
                    $item['warehouse_id'] = $pr['warehouse_Id'];
                    $this->addPharmaCanvas($item);
                }

                if (isset($request->isconsignments) && $request->isconsignments == 1) {
                    if ($item['item_Request_Qty'] > 0) {
                        // PurchaseOrderConsignment::create([
                        //     'pr_request_id' => $pr['id'],
                        //     'rr_id' => $request['consignmentid'],
                        //     'item_group_id' => $request->invgroup_id,
                        //     'category_id' =>$request->item_Category_Id,
                        //     'vendor_id' => $item['prepared_supplier_id'] ?? 0,
                        //     'createdby' => $user->idnumber,
                        // ]);
                        PurchaseOrderConsignmentItem::updateOrCreate(
                            [
                                'pr_request_id' => $pr['id'],
                                'rr_id' => $request['consignmentid'],
                                'request_item_id' => $item['item_Id'],
                            ],
                            [
                                'pr_request_id' => $pr['id'],
                                'po_consignment_id' => $po_consignment['id'],
                                'rr_id' => $request['consignmentid'],
                                'item_group_id' => $request->invgroup_id,
                                'category_id' => $request->item_Category_Id,
                                'request_item_id' => $item['item_Id'],
                                'consignmen_item_id' => $item['item_Id'],
                                'consignment_qty' => $item['rr_Detail_Item_Qty_Received'],
                                'request_qty' => $item['item_Request_Qty'],
                                'batch_id' => $item['batch_id'],
                                'createdby' => $user->idnumber,
                                'consignment_balance_qty' => $item['rr_Detail_Item_Qty_Received'] - $item['item_Request_Qty'],
                            ]
                        );

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

        $ismed = NULL;
        if ($this->role->pharmacy_warehouse()) {
            $ismed = 1;
        }
        if ($pr->isconsignment && $pr->isconsignment == 1) {
            $ismed = 1;
        }
        $isdiet = NULL;
        if ($this->role->isdietary()  || $this->role->isdietaryhead()) {
            $isdiet = 1;
        }
        $pr->update([
            'pr_Justication' => $request->pr_Justication,
            'pr_Transaction_Date_Required' => Carbon::parse($request->pr_Transaction_Date_Required),
            'pr_Priority_Id' => $request->pr_Priority_Id,
            'warehouse_Id' => (int)$request->department_id,
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
                        if (sizeof($pr->purchaseRequestAttachments)) {
                            foreach ($pr->purchaseRequestAttachments as $attach) {
                                File::delete(public_path() . $attach->filepath);
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

            if (isset($item["id"])) {
                $pr->purchaseRequestDetails()->where('id', $item['id'])->update([
                    'item_Id' => $item['item_Id'],

                    'item_ListCost' => $item['item_ListCost'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'item_Request_Qty' => $item['item_Request_Qty'],
                    'prepared_supplier_id' => isset($item['prepared_supplier_id']) ? $item['prepared_supplier_id'] : 0,
                    'recommended_supplier_id' => isset($item['prepared_supplier_id']) ? $item['prepared_supplier_id'] : 0,
                    'lead_time' => $item['lead_time'] ?? 0,
                    'vat_rate' => $item['vat_rate'] ?? 0,
                    'vat_type' => $item['vat_type'] ?? 1,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'vat_amount' => $item['vat_amount'] ?? 0,
                    'total_amount' => $item['total_amount'] ?? 0,
                    'total_net' => $item['total_net'] ?? 0,

                    'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                    'ismedicine' => $ismed,
                    'isdietary' => $isdiet


                    // 'item_Request_Qty' => $item['item_Request_Qty'],
                    // 'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                    // 'prepared_supplier_id' => $item['prepared_supplier_id'] ?? 0,
                ]);

                if ($this->role->pharmacy_warehouse() || $this->role->isdietary()) {
                    $details =  $pr->purchaseRequestDetails()->where('pr_request_id', $pr['id'])->where('item_Id', $item['item_Id'])->first();
                    $item['id'] = $details->id;
                    $item['pr_id'] = $pr['id'];
                    $item['vat_rate'] = $details->vat_rate;
                    $item['vat_type'] = $details->vat_type;
                    $item['discount'] = $details->discount;
                    $item['discount_amount'] = $details->discount_amount;
                    $item['vat_rate'] = $details->vat_rate;
                    $item['vat_amount'] = $details->vat_amount;
                    $item['total_amount'] = $details->total_amount;
                    $item['total_net'] = $details->total_net;
                    $item['lead_time'] = $details->lead_time;
                    $item['warehouse_id'] = $request->department_id;
                    $this->addPharmaCanvas($item);
                }
            } else {
                $pr->purchaseRequestDetails()->create([
                    'filepath' => $file[0] ?? null,
                    'filename' => $file[2] ?? null,
                    'item_Id' => $item['item_Id'],

                    'item_ListCost' => $item['item_ListCost'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'item_Request_Qty' => $item['item_Request_Qty'],
                    'prepared_supplier_id' => isset($item['prepared_supplier_id']) ? $item['prepared_supplier_id'] : 0,
                    'recommended_supplier_id' => isset($item['prepared_supplier_id']) ? $item['prepared_supplier_id'] : 0,
                    'lead_time' => $item['lead_time'] ?? 0,
                    'vat_rate' => $item['vat_rate'] ?? 0,
                    'vat_type' => $item['vat_type'] ?? 0,
                    'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                    'ismedicine' => $ismed,
                    'isdietary' => $isdiet

                    // 'item_Request_Qty' => $item['item_Request_Qty'],
                    // 'prepared_supplier_id' => $item['prepared_supplier_id'] ?? 0,
                    // 'item_Request_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                ]);
                if ($this->role->pharmacy_warehouse() || $this->role->isdietary()) {
                    $details =  $pr->purchaseRequestDetails()->where('pr_request_id', $pr['id'])->where('item_Id', $item['item_Id'])->first();
                    $item['id'] = $details->id;
                    $item['pr_id'] = $pr['id'];
                    $item['vat_rate'] = $details->vat_rate;
                    $item['vat_type'] = $details->vat_type;
                    $item['discount'] = $details->discount;
                    $item['discount_amount'] = $details->discount_amount;
                    $item['vat_rate'] = $details->vat_rate;
                    $item['vat_amount'] = $details->vat_amount;
                    $item['total_amount'] = $details->total_amount;
                    $item['total_net'] = $details->total_net;
                    $item['lead_time'] = $details->lead_time;
                    $item['warehouse_id'] = $request->department_id;
                    $this->addPharmaCanvas($item);
                }
            }
        }

        return response()->json(["message" => "success"], 200);
    }

    public function removeItem($id)
    {

        $prdetail = PurchaseRequestDetails::where('id', $id)->first();

        if ($prdetail->canvases()->where('pr_request_details_id', $id)->exists()) {
            $prdetail->canvases()->where('pr_request_details_id', $id)->delete();
        }

        $prdetail->delete();

        return PurchaseRequestDetails::where('id', $id)->delete();
    }

    public function updateItemAttachment(Request $request, $id)
    {
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
            File::delete(public_path() . $attachment->filepath);
            $attachment->delete();
        }
        foreach ($pr->purchaseRequestDetails as $detail) {
            File::delete(public_path() . $detail->filepath);
            if ($detail->canvases()->where('pr_request_details_id', $detail->id)->exists()) {
                $detail->canvases()->where('pr_request_details_id', $detail->id)->delete();
            }
            $detail->delete();
        }
        $pr->delete();
        return response()->json(["message" => "success"], 200);
    }

    public function approveItems(Request $request)
    {
        if (Auth::user()->role->name == 'department head' || Auth::user()->role->name == 'dietary head') {
            $this->approveByDepartmentHead($request);
        } else if (Auth::user()->role->name == 'administrator') {
            $this->approveByAdministrator($request);
        } else if (Auth::user()->role->name == 'consultant') {
            return $this->approveByConsultant($request);
        }
        return response()->json(["message" => "success"], 200);
    }
 private function autoCreatePO($pr,$canvas_details){
        if($pr['branch_Id'] == 1){
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'PO1', 'branch_id' => $pr['branch_Id']])->first();
        }else{
            $sequence = SystemSequence::where(['isActive' => true, 'code' => 'PO7', 'branch_id' => $pr['branch_Id']])->first();
        }
        $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
        $prefix = $sequence->seq_prefix;
        $suffix = $sequence->seq_suffix;

        $po_Document_discount_percent = 0;
        $po_Document_discount_amount = 0;
        $po_Document_vat_amount = 0;       
        $po_Document_total_net_amount = 0;
        $po_Document_total_gross_amount = 0;
        $vendor_id = $canvas_details['vendor_id'];  

        $canvas = CanvasMaster::where('pr_request_id', $pr->id)->whereNull('canvas_Level2_CancelledBy')->where('vendor_id',$vendor_id)->get();

        $terms_id  = $canvas_details['terms_id'];           
        $currency_id  = $canvas_details['currency_id'];           
        $canvas_lead_time  = $canvas_details['canvas_lead_time']; 
       
        if ($canvas->isNotEmpty()) {
            $terms_id = $canvas->first()->terms_id;           
            $currency_id = $canvas->first()->currency_id;           
            $canvas_lead_time = $canvas->first()->canvas_lead_time; 
        
            $po_Document_discount_percent = $canvas->sum(function ($item) {
                return round($item->canvas_item_discount_percent, 4);
            });
        
            $po_Document_discount_amount = $canvas->sum(function ($item) {
                return round($item->canvas_item_discount_amount, 4);
            });
        
            $po_Document_vat_amount = $canvas->sum(function ($item) {
                return round($item->canvas_item_vat_amount, 4);
            });
        
            $po_Document_total_gross_amount = $canvas->sum(function ($item) {
                return round($item->canvas_item_total_amount, 4);
            });
        
            $po_Document_total_net_amount = $canvas->sum(function ($item) {
                return round($item->canvas_item_net_amount, 4);
            });
        }

        $po = purchaseOrderMaster::whereNull('comptroller_approved_by')->updateOrCreate(
            [
                'pr_request_id' => $pr['id'],
                'po_Document_vendor_id' => $vendor_id,
            ],
            [
                'po_Document_number' => $number,
                'po_Document_prefix' => $prefix,
                'po_Document_suffix' => $suffix,
                'po_Document_branch_id' => (int)$pr['branch_Id'],
                'po_Document_warehouse_group_id' => (int)3, 
                'po_Document_warehouse_id' =>  (int)$pr['warehouse_Id'],
                'po_Document_transaction_date' => Carbon::now(),
                'po_Document_vendor_id' => (int)$vendor_id,
                'po_Document_terms_id' => (int)$terms_id,
                'po_Document_currency_id' => (int)$currency_id,
                'po_Document_expected_deliverydate' => Carbon::now()->addDays($canvas_lead_time),
                'po_Document_due_date_unit' => (int)$canvas_details['canvas_Item_UnitofMeasurement_Id'],
                'po_Document_due_date_value' =>(int)$canvas_lead_time,
                'po_Document_overdue_date_value' => 0,
                'po_Document_total_item_ordered' => sizeof($canvas),
                'po_Document_total_gross_amount' => $po_Document_total_gross_amount,
                'po_Document_discount_percent' =>  $po_Document_discount_percent,
                'po_Document_discount_amount' =>  $po_Document_discount_amount,
                'po_Document_isvat_inclusive' => $canvas_details['vat_type'] == 1 ? 1 : NULL,
                'po_Document_vat_percent' => $canvas_details['canvas_item_vat_rate'],
                'po_Document_vat_amount' => $po_Document_vat_amount,
                'po_Document_total_net_amount' => $po_Document_total_net_amount,
                'pr_request_id' => $pr['id'],
                'po_Document_userid' => $canvas_details['canvas_Document_CanvassBy'],
                'po_status_id' => 1,
            ]
        );

        foreach ($canvas as $item) {
            $po->details()->updateOrCreate(
                [
                    'po_Detail_item_id'=>  $item['canvas_Item_Id'],
                    'pr_detail_id'=>  $item['pr_request_details_id'],
                    'canvas_id'=>  $item['id'],
                ],
                [
                'po_Detail_item_id' => $item['canvas_Item_Id'],
                'po_detail_currency_id' => $item['currency_id'],
                'po_Detail_item_listcost' => $item['canvas_item_net_amount'],
                'po_Detail_item_qty' => $item['canvas_Item_Qty'],
                'po_Detail_item_unitofmeasurement_id' => $item['canvas_Item_UnitofMeasurement_Id'],
                'po_Detail_item_discount_percent' => $item['canvas_item_discount_percent'],
                'po_Detail_item_discount_amount' => $item['canvas_item_discount_amount'],
                'po_Detail_vat_percent' => $item['canvas_item_vat_rate'],
                'po_Detail_vat_amount' => $item['canvas_item_vat_amount'],
                'po_Detail_net_amount' => round($item['canvas_item_net_amount'], 4),
                'pr_detail_id' => $item['pr_request_details_id'],
                'canvas_id' => $item['id'],
                'isFreeGoods' => $item['isFreeGoods'],
            ]);
            $updatePO = purchaseOrderMaster::where('id', $po['id'])->first();
            if($updatePO){
                purchaseOrderMaster::where('id', $po['id'])->update([
                    'po_Document_terms_id'=> $item['terms_id']
                ]);
            }

            $checkifconsignment = PurchaseOrderConsignment::where('pr_request_id', $pr->id)->where('vendor_id',$vendor_id)->first();
            if($checkifconsignment){
            
                $checkifconsignmentItem = PurchaseOrderConsignmentItem::where('pr_request_id', $pr->id)->where('request_item_id',$item['item_Id'])->first();
                $checkifconsignment->update([
                    'po_id' => $po['id'],
                    'canvas_id' => $item['recommended_canvas']['id'],
                    'total_gross_amount' => $po_Document_total_gross_amount,
                    'discount_percent' =>  $po_Document_discount_percent,
                    'discount_amount' =>  $po_Document_discount_amount,
                    'isvat_inclusive' => $canvas_details['vat_type'] == 1 ? 1 : NULL,
                    'vat_percent' => $canvas_details['canvas_item_vat_rate'],
                    'vat_amount' => $po_Document_vat_amount,
                    'total_net_amount' => $po_Document_total_net_amount,
                    'updatedby' => $canvas_details['canvas_Document_CanvassBy']
                ]);

                $checkifconsignmentItem->update([
                    'po_id' => $po['id'],
                    'canvas_id' => $item['id'],
                    'item_listcost' => $item['canvas_item_amount'],
                    'item_qty' => $item['canvas_Item_Qty'],
                    'item_unitofmeasurement_id' => $item['canvas_Item_UnitofMeasurement_Id'],
                    'item_discount_percent' => $item['canvas_item_discount_percent'],
                    'item_discount_amount' => $item['canvas_item_discount_amount'],
                    'vat_percent' => $item['canvas_item_vat_rate'],
                    'vat_amount' => $item['canvas_item_vat_amount'],
                    'total_gross' => $item['canvas_item_total_amount'],
                    'net_amount' => round($item['canvas_item_net_amount'], 4),
                    'updatedby' => $canvas_details['canvas_Document_CanvassBy']
                ]);
            } 
        }
        $getFreeGoods = CanvasMaster::where('pr_request_id',$pr->id)->where('vendor_id',$vendor_id)->where('isFreeGoods',1)->get();
                
        if(count($getFreeGoods) > 0){
            $PurchaseOrderDetails = PurchaseOrderDetails::where('po_id',$po->id)->first();
            foreach ($getFreeGoods as $item) {
                $po->details()->updateOrCreate(
                    [
                        'pr_detail_id'=>  $item['pr_request_details_id'],
                        'canvas_id'=>  $item['id'],
                    ],
                    [
                    'po_Detail_item_id' => $item['canvas_Item_Id'],
                    'po_detail_currency_id' => $item['currency_id'],
                    'po_Detail_item_listcost' => $item['canvas_item_net_amount'],
                    'po_Detail_item_qty' => $item['canvas_Item_Qty'],
                    'po_Detail_item_unitofmeasurement_id' => $item['canvas_Item_UnitofMeasurement_Id'],
                    'po_Detail_item_discount_percent' => $item['canvas_item_discount_percent'],
                    'po_Detail_item_discount_amount' => $item['canvas_item_discount_amount'],
                    'po_Detail_vat_percent' => $item['canvas_item_vat_rate'],
                    'po_Detail_vat_amount' => $item['canvas_item_vat_amount'],
                    'po_Detail_net_amount' => round($item['canvas_item_net_amount'], 4),
                    'pr_detail_id' => $item['id'],
                    'canvas_id' => $item['id'],
                    'comptroller_approved_by' =>$PurchaseOrderDetails->comptroller_approved_by,
                    'comptroller_approved_date' => $PurchaseOrderDetails->comptroller_approved_date,
                    'admin_approved_by' =>$PurchaseOrderDetails->admin_approved_by,
                    'admin_approved_date' => $PurchaseOrderDetails->admin_approved_date,
                    'corp_admin_approved_by' =>$PurchaseOrderDetails->corp_admin_approved_by,
                    'corp_admin_approved_date' => $PurchaseOrderDetails->corp_admin_approved_date,
                    'ysl_approved_by' =>$PurchaseOrderDetails->ysl_approved_by,
                    'ysl_approved_date' => $PurchaseOrderDetails->ysl_approved_date,
                    'isFreeGoods' => $item['isFreeGoods'],
                ]);
            }
        }
        $sequence->update([
            'seq_no' => (int) $sequence->seq_no + 1,
            'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
        ]);
    }

    private function approveByConsultant($request)
    {
        $sequence = SystemSequence::where(['isActive' => true, 'code' => 'CTCR1'])->first();
        $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
        $prefix = $sequence->seq_prefix;
        $suffix = $sequence->seq_suffix;

        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $items = isset($request->items) ? $request->items : $request->purchase_request_details;
            foreach ($items as $key => $item) {
                $prd  = PurchaseRequestDetails::where('id', $item['id'])->first();
                // return Auth::user()->role->name;
                if (!Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant) {
                    // if($this->role->pharmacy_warehouse()){
                    //     $this->addPharmaCanvas($item);
                    // }
                }

                if (Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant) {
                    if ($request->branch_Id != 1) {
                        if (isset($item['isapproved']) && $item['isapproved'] == true) {
                            $prd->update([
                                'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                                'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                                'item_Request_Department_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                                'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            ]);
                        } else {
                            $prd->update([
                                'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                                'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                            ]);
                        }
                        if (isset($item['isapproved']) && $item['isapproved'] == true) {
                            $prd->update([
                                'pr_Branch_Level2_ApprovedBy' => Auth::user()->idnumber,
                                'pr_Branch_Level2_ApprovedDate' => Carbon::now(),

                                'item_Branch_Level1_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                                'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                                'item_Branch_Level2_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                                'item_Branch_Level2_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                                // 'is_submitted' => 1,
                            ]);
                        } else {
                            $prd->update([
                                'pr_Branch_Level2_CancelledBy' => Auth::user()->idnumber,
                                'pr_Branch_Level2_CancelledDate' => Carbon::now(),
                            ]);
                        }
                    } else {
                        if (isset($item['isapproved']) && $item['isapproved'] == true) {
                            $prd->update([
                                'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                                'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                                'item_Request_Department_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                                'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            ]);
                        } else {
                            $prd->update([
                                'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                                'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                            ]);
                        }
                    }
                } else {
                    if (isset($item['isapproved']) && $item['isapproved'] == true) {
                        $prd->update([
                            'pr_Branch_Level2_ApprovedBy' => Auth::user()->idnumber,
                            'pr_Branch_Level2_ApprovedDate' => Carbon::now(),
                            'item_Branch_Level1_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                            'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            'item_Branch_Level2_Approved_Qty' => $item['item_Request_Department_Approved_Qty'] ?? $item['item_Request_Qty'],
                            'item_Branch_Level2_Approved_UnitofMeasurement_Id' => $item['item_Request_Department_Approved_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            'is_submitted' => 1,
                        ]);
                        if ($this->role->pharmacy_warehouse()){
                            $canvas = CanvasMaster::where('pr_request_details_id', $prd->id)->where('canvas_Item_Id',$prd->item_Id)->where('isRecommended',1)->first();

                            $canvas->update([
                                'canvas_Level2_ApprovedBy' => 'auto',
                                'canvas_Level2_ApprovedDate' => Carbon::now(),
                                'canvas_Document_Approved_Number' => generateCompleteSequence($prefix, $number, $suffix, "")
                            ]);
                            $this->autoCreatePO($pr,$canvas);

                        }
                    } else {
                        $prd->update([
                            'pr_Branch_Level2_CancelledBy' => Auth::user()->idnumber,
                            'pr_Branch_Level2_CancelledDate' => Carbon::now(),
                        ]);
                    }
                }
            }
            if (Auth()->user()->isDepartmentHead && Auth()->user()->isConsultant) {
                $pr = PurchaseRequest::where('id', $request->id)->first();
                if ($request->branch_Id != 1) {
                    if ($request->isapproved) {
                        $pr->update([
                            'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                        ]);
                    } else {
                        $pr->update([
                            'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                            'pr_DepartmentHead_Cancelled_Remarks' => $request->remarks,
                            'pr_Status_Id' => 3
                        ]);
                    }

                    if ($request->isapproved) {
                        $pr->update([
                            'pr_Branch_Level2_ApprovedBy' => Auth::user()->idnumber,
                            'pr_Branch_Level2_ApprovedDate' => Carbon::now(),
                            'pr_Status_Id' => 6
                        ]);
                    } else {
                        $pr->update([
                            'pr_Branch_Level2_CancelledBy' => Auth::user()->idnumber,
                            'pr_Branch_Level2_CancelledDate' => Carbon::now(),
                            'pr_Branch_Level2_Cancelled_Remarks' => $request->remarks,
                            'pr_Status_Id' => 3
                        ]);
                    }
                } else {
                    if ($request->isapproved) {
                        $pr->update([
                            'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                        ]);
                    } else {
                        $pr->update([
                            'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                            'pr_DepartmentHead_Cancelled_Remarks' => $request->remarks,
                            'pr_Status_Id' => 3
                        ]);
                    }
                }
            } else {
                $pr = PurchaseRequest::where('id', $request->id)->first();
                if ($request->isapproved) {
                    $pr->update([
                        'pr_Branch_Level2_ApprovedBy' => Auth::user()->idnumber,
                        'pr_Branch_Level2_ApprovedDate' => Carbon::now(),
                        'pr_Status_Id' => 6
                    ]);
                    if ($this->role->pharmacy_warehouse()){
                        $sequence->update([
                            'seq_no' => (int) $sequence->seq_no + 1,
                            'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
                        ]);
                    }

                } else {
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

    private function addPharmaCanvas($item)
    {
        $vendor = Vendors::findOrfail($item['prepared_supplier_id']);
        $sequence = SystemSequence::where(['isActive' => true, 'code' => 'CSN1'])->first();
        $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
        $prefix = $sequence->seq_prefix;
        $suffix = $sequence->seq_suffix;

        // $discount_amount = 0;
        // $vat_amount = 0;
        // $total_amount = $item['item_ListCost'] * $item['item_Request_Qty'];
        // if ($item['vat_rate']) {
        //     if ($vendor->isVATInclusive == 0) {
        //         $vat_amount = $total_amount * ($item['vat_rate'] / 100);
        //         $total_amount += $vat_amount;
        //     } else {
        //         $vat_amount = $total_amount * ($item['vat_rate'] / 100);
        //     }
        // }
        // if ($item['discount']) {
        //     $discount_amount = $total_amount * ($item['discount'] / 100);
        // }


        // if ($item['vat_rate']) {
        //     if ($vendor->isVATInclusive == 0) {
        //         $vat_amount = $total_amount * ($item['vat_rate'] / 100);
        //         $total_amount += $vat_amount;
        //     } else {
        //         $vat_amount = $total_amount * ($item['vat_rate'] / 100);
        //     }
        // }
        // if ($item['discount']) {
        //     $discount_amount = $total_amount * ($item['discount'] / 100);
        // }

        $pr_id = Request()->id ?? $item['pr_id'];

        CanvasMaster::updateOrCreate(
            [
                'pr_request_id' => $pr_id,
                'pr_request_details_id' => $item['id'],
                'canvas_Item_Id' => $item['item_Id'],
                'vendor_id' => $vendor->id,
            ],
            [
                'canvas_Document_Number' => $number,
                'canvas_Document_Prefix' => $prefix,
                'canvas_Document_Suffix' => $suffix,
                'canvas_Document_CanvassBy' => Request()->pr_RequestedBy ?? Auth()->user()->idnumber,
                'canvas_Document_Transaction_Date' => Carbon::now(),
                'requested_date' => Carbon::now(),
                'canvas_Branch_Id' => Auth()->user()->branch_id,
                'canvas_Warehouse_Group_Id' => Request()->warehouse['warehouse_Group_Id'] ?? '1',
                'canvas_Warehouse_Id' =>  $item['warehouse_id'],
                'vendor_id' => $vendor->id,
                'pr_request_id' => $pr_id,
                'pr_request_details_id' => $item['id'],
                'canvas_Item_Id' => $item['item_Id'],
                'canvas_Item_Qty' => $item['item_Request_Qty'],
                'canvas_Item_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'],
                'canvas_item_amount' => $item['item_ListCost'],

                'canvas_item_total_amount' => $item['total_amount'],
                'canvas_item_discount_percent' => $item['discount'],
                'canvas_item_discount_amount' => $item['discount_amount'],
                'canvas_item_net_amount' => $item['total_net'],
                'canvas_lead_time' => $item['lead_time'],
                // 'canvas_remarks' => $request->canvas_remarks,
                'currency_id' => 1,
                'canvas_item_vat_rate' => $item['vat_rate'],
                'canvas_item_vat_amount' => $item['vat_amount'],
                'vat_type' => $item['vat_type'],
                'isFreeGoods' => null,
                'isRecommended' => 1,
                'terms_id' => 10,
                'created_at' => Carbon::now(),
                // 'canvas_Level2_ApprovedBy' => Request()->pr_RequestedBy,
                // 'canvas_Level2_ApprovedDate' => Carbon::now(),
            ]
        );

        $sequence->update([
            'seq_no' => (int) $sequence->seq_no + 1,
            'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
        ]);
    }

    private function approveByDepartmentHead($request)
    {
        $items = isset($request->items) ? $request->items : $request->purchase_request_details;
        $pr = PurchaseRequest::where('id', $request->id)->first();
        foreach ($items as $key => $item) {
            $prd  = PurchaseRequestDetails::where('id', $item['id'])->first();
            // return Auth::user()->role->name;
            if (isset($item['isapproved']) && $item['isapproved'] == true) {
                if ($pr->isdietary == 1) {
                    $prd->update([
                        'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                        'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                        'item_Request_Department_Approved_Qty' => $item['item_Request_Qty'] ?? $item['item_Request_Qty'],
                        'item_Request_Qty' => $item['item_Request_Qty'] ?? $item['item_Request_Qty'],
                        'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],

                        'pr_Branch_Level1_ApprovedBy' => 'auto',
                        'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                        'item_Branch_Level1_Approved_Qty' => $item['item_Request_Qty'] ?? $item['item_Request_Qty'],
                        'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                        'is_submitted' => 1,
                    ]);

                    $discount_amount = 0;
                    $vat_amount = 0;
                    $total_amount = $prd->item_ListCost * $prd->item_Request_Qty;

                    if ($prd->discount) {
                        $discount_amount = $total_amount * ($prd->discount / 100);
                    }

                    if ($prd->vat_rate) {
                        $vat_amount = ($total_amount - $discount_amount) * ($prd->vat_rate / 100);
                    }
                    $canvas_item_total_amount = ($total_amount - $discount_amount) + $vat_amount;

                    CanvasMaster::where(
                        [
                            'pr_request_details_id' => $prd->id,
                            'canvas_Item_Id' => $prd->item_Id,
                            'vendor_id' => $prd->prepared_supplier_id,
                        ]
                    )->update(
                        [
                            'vendor_id' => $prd->prepared_supplier_id,
                            'canvas_Branch_Id' => Auth()->user()->branch_id,
                            'canvas_Item_Qty' => $prd->item_Request_Qty,
                            'canvas_item_amount' => $prd->item_ListCost,
                            'canvas_item_total_amount' => $prd->total_amount,
                            'canvas_item_discount_percent' => $prd->discount,
                            'canvas_item_discount_amount' => $prd->discount_amount,
                            'canvas_item_net_amount' => $prd->total_net,
                            'canvas_lead_time' => $prd->lead_time,
                            // 'canvas_remarks' => $request->canvas_remarks,
                            'currency_id' => 1,
                            'canvas_item_vat_rate' => $prd->vat_rate,
                            'canvas_item_vat_amount' => $prd->vat_amount,
                            'vat_type' => $prd->vat_type,
                        ]
                    );
                } else if ($pr->ismedicine == 1) {
                    $canvas = CanvasMaster::where('pr_request_details_id', $prd->id)->where('canvas_Item_Id', $prd->item_Id)->where('vendor_id', $prd->prepared_supplier_id)->first();
                    $listcost =  $item['item_ListCost'];
                    $qty =  $item['item_Request_Qty'];
                    $vat =  $prd->vat_rate;
                    $discount =  $prd->discount;
                    if ($canvas->canvas_item_amount) {
                        $listcost = $canvas->canvas_item_amount;
                        $qty = $canvas->canvas_Item_Qty;
                        $vat = $canvas->canvas_item_vat_rate;
                        $discount = $canvas->canvas_item_discount_percent;
                    }
                    $prd->update([
                        'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                        'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                        'item_Request_Department_Approved_Qty' => $item['item_Request_Qty'] ?? $item['item_Request_Qty'],
                        'item_ListCost' =>  $listcost ?? 0,
                        'item_Request_Qty' => $qty,
                        'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                    ]);



                    // $discount_amount = 0;
                    // $total_discount = 0;
                    // $vat_amount = 0;
                    // $total_amount = $listcost * $qty;

                    // // Handle VAT based on vat_type
                    // if ($canvas->vat_type == 1) { // Exclusive VAT
                    //     $vat_amount     = $total_amount * ($vat / 100);
                    //     $total_amount   += $vat_amount;
                    // } elseif ($canvas->vat_type == 2) { // Inclusive VAT
                    //     $vat_amount     = $total_amount * ($vat / (100 + $vat)); // Extract VAT from total
                    // } elseif ($canvas->vat_type == 3) { // Exempt VAT
                    //     $vat_amount     = 0; // No VAT for exempt
                    // }

                    // // Apply discount if applicable
                    // if ($discount) {
                    //     $discount_amount         = $total_amount * ($discount / 100);
                    //     $total_discount += $discount_amount;
                    // }

                    // $total_net                   = $total_amount - $discount_amount;

                    // // Calculate final total amount including VAT
                    // $canvas_item_total_amount = ($total_amount - $discount_amount) + $vat_amount;
                    // CanvasMaster::where(
                    //     [
                    //         'pr_request_details_id' => $prd->id,
                    //         'canvas_Item_Id' => $prd->item_Id,
                    //         'vendor_id' => $prd->prepared_supplier_id,
                    //     ]
                    // )->update(
                    //     [
                    //         'vendor_id' => $prd->prepared_supplier_id,
                    //         'canvas_Branch_Id' => Auth()->user()->branch_id,
                    //         'canvas_Item_Qty' => $qty,
                    //         'canvas_item_amount' => $listcost,
                    //         'canvas_item_total_amount' => $total_amount,
                    //         'canvas_item_discount_percent' => $discount,
                    //         'canvas_item_discount_amount' => $discount_amount,
                    //         'canvas_item_net_amount' => $total_net,
                    //         'canvas_lead_time' => $canvas->canvas_lead_time,
                    //         // 'canvas_remarks' => $request->canvas_remarks,
                    //         'currency_id' => 1,
                    //         'canvas_item_vat_rate' => $vat,
                    //         'canvas_item_vat_amount' => $vat_amount,
                    //         'vat_type' => $canvas->vat_type,
                    //     ]
                    // );
                } else {

                    if (Auth()->user()->warehouse_id == '36') {
                        $prd->update([
                            'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_ApprovedDate' => Carbon::now(),


                            'pr_Branch_Level1_ApprovedBy' => 'auto',
                            'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                            'item_Request_Department_Approved_Qty' => $item['item_Request_Qty'] ?? $item['item_Request_Qty'],
                            'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                            // 'is_submitted' => fa,
                        ]);
                    } else {
                        $prd->update([
                            'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                            'pr_DepartmentHead_ApprovedDate' => Carbon::now(),

                            'item_Request_Department_Approved_Qty' => $item['item_Request_Qty'] ?? $item['item_Request_Qty'],
                            'item_Request_Department_Approved_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                        ]);
                    }
                }
            } else {
                $prd->update([
                    'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                    'pr_DepartmentHead_CancelledDate' => Carbon::now(),

                    'pr_Branch_Level1_CancelledBy' => 'auto',
                    'pr_Branch_Level1_CancelledDate' => Carbon::now(),
                ]);

                if ($this->role->pharmacy_warehouse()) {
                    $canvas = CanvasMaster::where('pr_request_details_id', $prd->id)->where('canvas_Item_Id', $prd->item_Id)->where('vendor_id', $prd->prepared_supplier_id)->first();
                    $canvas->update(
                       [
                        'canvas_Level2_CancelledBy' => Auth::user()->idnumber,
                        'canvas_Level2_CancelledDate' => Carbon::now()
                       ]
                    );
                }
            }
        }

        if ($request->isapproved) {
            if ($pr->isdietary == 1 || Auth()->user()->warehouse_id == '36') {
                $pr->update([
                    'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                    'pr_DepartmentHead_ApprovedDate' => Carbon::now(),

                    'pr_Branch_Level1_ApprovedBy' => 'auto',
                    'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                    'pr_Status_Id' => 6
                ]);
            } else {

                $pr->update([
                    'pr_DepartmentHead_ApprovedBy' => Auth::user()->idnumber,
                    'pr_DepartmentHead_ApprovedDate' => Carbon::now(),
                ]);
            }
        } else {
            $pr->update([
                'pr_DepartmentHead_CancelledBy' => Auth::user()->idnumber,
                'pr_DepartmentHead_CancelledDate' => Carbon::now(),
                'pr_DepartmentHead_Cancelled_Remarks' => $request->remarks,
                'pr_Status_Id' => 3
            ]);
        }
    }

    private function approveByAdministrator($request)
    {
        $items = isset($request->items) ? $request->items : $request->purchase_request_details;
        foreach ($items as $key => $item) {
            $prd  = PurchaseRequestDetails::where('id', $item['id'])->first();
            if (isset($item['isapproved']) && $item['isapproved'] == true) {
                $prd->update([
                    'pr_Branch_Level1_ApprovedBy' => Auth::user()->idnumber,
                    'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                    'item_Branch_Level1_Approved_Qty' => $item['item_Request_Qty'] ?? $item['item_Request_Qty'],
                    'item_Branch_Level1_Approved_UnitofMeasurement_Id' => $item['item_Request_UnitofMeasurement_Id'] ?? $item['item_Request_UnitofMeasurement_Id'],
                ]);
            } else {
                $prd->update([
                    'pr_Branch_Level1_CancelledBy' => Auth::user()->idnumber,
                    'pr_Branch_Level1_CancelledDate' => Carbon::now(),
                ]);
            }
        }
        $pr = PurchaseRequest::where('id', $request->id)->first();
        if ($request->isapproved) {
            $pr->update([
                'pr_Branch_Level1_ApprovedBy' => Auth::user()->idnumber,
                'pr_Branch_Level1_ApprovedDate' => Carbon::now(),
                'pr_Status_Id' => 6
            ]);
        } else {
            $pr->update([
                'pr_Branch_Level1_CancelledBy' => Auth::user()->idnumber,
                'pr_Branch_Level1_CancelledDate' => Carbon::now(),
                'pr_Branch_Level1_Cancelled_Remarks' => $request->remarks,
                'pr_Status_Id' => 3
            ]);
        }
    }

    public function voidPR($id, Request $request)
    {
        return PurchaseRequest::findOrfail($id)->update([
            'isvoid' => 1,
        ]);
    }
}
