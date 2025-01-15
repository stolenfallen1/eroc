<?php

namespace App\Http\Controllers\MMIS;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\ParentRole;
use Illuminate\Http\Request;
use App\Models\BuildFile\Vendors;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\MMIS\inventory\Delivery;
use App\Models\BuildFile\SystemSequence;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Unitofmeasurement;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\PurchaseRequest;
use App\Models\MMIS\procurement\purchaseOrderMaster;
use App\Models\MMIS\procurement\PurchaseOrderDetails;
use App\Models\MMIS\inventory\PurchaseOrderConsignment;
use App\Helpers\SearchFilter\Procurements\PurchaseOrders;
use App\Models\MMIS\inventory\PurchaseOrderConsignmentItem;

class PurchaseOrderController extends Controller
{
    protected $model;
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
        return (new PurchaseOrders)->searchable();
    }

    public function getCount()
    {

       if($this->role->purchaser()){
            $branch = Request()->branch_id ? Request()->branch_id : Auth()->user()->branch_id;
            $department_id = Request()->department_id != '' ? Request()->department_id : NULL;
            $comptroller_count = DB::connection('sqlsrv_mmis')->select("SET NOCOUNT ON; EXEC PurchaseOrderForApprovalCount @branch_id = ?, @warehouse_id = ?, @approver_type = ?", [$branch, $department_id, 'comptroller']);
            $admin_count = DB::connection('sqlsrv_mmis')->select("SET NOCOUNT ON; EXEC PurchaseOrderForApprovalCount @branch_id = ?, @warehouse_id = ?, @approver_type = ?", [$branch, $department_id, 'admin']);
            $corporate_count = DB::connection('sqlsrv_mmis')->select("SET NOCOUNT ON; EXEC PurchaseOrderForApprovalCount @branch_id = ?, @warehouse_id = ?, @approver_type = ?", [$branch, $department_id, 'corporate_admin']);
            $president_count = DB::connection('sqlsrv_mmis')->select("SET NOCOUNT ON; EXEC PurchaseOrderForApprovalCount $branch,'','president'");
            return response()->json([
                'comptroller_count'     => (int)$comptroller_count[0]->ApprovalCount ?? 0,
                'administrator_count'   => (int)$admin_count[0]->ApprovalCount ?? 0,
                'corp_admin_count'      => (int)$corporate_count[0]->ApprovalCount ?? 0,
                'president_count'       => (int)$president_count[0]->ApprovalCount ?? 0,
            ]);
        }
    }

    public function show($id)
    {
        return purchaseOrderMaster::with(['details' => function ($q) {
            if (Request()->tab == 6) {
                $q->with('item', 'unit', 'purchaseRequestDetail.recommendedCanvas');
            } else {
                $q->with('item', 'unit', 'purchaseRequestDetail.recommendedCanvas');
            }
        }, 'purchaseRequest' => function ($q) {
            $q->with('user', 'itemGroup', 'category', 'purchaseRequestAttachments');
        }, 'vendor', 'warehouse', 'user'])->findOrfail($id);
    }

    public function getByNumber()
    {

        $has_delivery = Delivery::whereRaw("CONCAT(po_Document_prefix,'',po_Document_number,'',po_Document_suffix) = ?", Request()->number)
            ->where('rr_Status', 11)->exists();
        if ($has_delivery) return response()->json(['error' => 'PO already exist'], 200);

        return purchaseOrderMaster::with([
            'latestdelivery.items' => function ($q) {
                $q->where('rr_Detail_Item_Qty_BackOrder', '!=', 0);
            },
            'details' => function ($q) {
                $q->with('item.authWarehouseItem', 'unit', 'purchaseRequestDetail.recommendedCanvas');
            },
            'podetails' => function ($q) {
                $q->with('item.authWarehouseItem', 'unit', 'purchaseRequestDetail.recommendedCanvas');
            },
            'purchaseRequest' => function ($q) {
                $q->with('user', 'itemGroup', 'category');
            },
            'vendor',
            'warehouse',
            'user'
        ])
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereHas('purchaseRequest', function ($query) {
                        $query->where('invgroup_id', 2);
                    })->where(function ($q2) {
                        $q2->where('corp_admin_approved_by', '!=', NULL)->orWhere('admin_approved_by', '!=', NULL);
                    });
                })->orWhere(function ($q1) {
                    $q1->whereHas('purchaseRequest', function ($query) {
                        $query->where('invgroup_id', '!=', 2);
                    })->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->where('po_Document_total_net_amount', '<', 100000)->where(function ($q4) {
                                $q4->where('corp_admin_approved_by', '!=', NULL)->orWhere('admin_approved_by', '!=', NULL);
                            });
                        })->orWhere(function ($q3) {
                            $q3->where('po_Document_total_net_amount', '>', 99999)->where('ysl_approved_by', '!=', NULL);
                        });
                    });
                });
            })
            ->whereRaw("CONCAT(po_Document_prefix,'',po_Document_number,'',po_Document_suffix) = ?", Request()->number)
            ->whereDoesntHave('delivery', function ($q) {
                $q->where('rr_Status', 11);
            })
            // ->whereHas('purchaseRequest', function($q){
            //     if(Auth::user()->role->name == 'dietary' || Auth::user()->role->name == 'dietary head'){
            //         $q->where('isPerishable', 1);
            //     }else{
            //         $q->where(function($q1){
            //             $q1->where('isPerishable', 0)->orWhere('isPerishable', NULL);
            //         });
            //     }
            // })
            ->first();
    }

    public function reconsider(Request $request)
    {
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $column1 = '';
            $column2 = '';
            $column3 = '';

            if ($authUser->role->name == 'president') {
                $column1 = 'ysl_cancelled_by';
                $column2 = 'ysl_cancelled_date';
                $column3 = 'ysl_cancelled_remarks';
            } elseif ($authUser->role->name == 'comptroller') {
                $column1 = 'comptroller_cancelled_by';
                $column2 = 'comptroller_cancelled_date';
                $column3 = 'comptroller_cancelled_remarks';
            } elseif ($authUser->role->name == 'administrator') {
                $column1 = 'admin_cancelled_by';
                $column2 = 'admin_cancelled_date';
                $column3 = 'admin_cancelled_remarks';
            } elseif ($authUser->role->name == 'corporate admin') {
                $column1 = 'corp_admin_cancelled_by';
                $column2 = 'corp_admin_cancelled_date';
                $column3 = 'corp_admin_cancelled_remarks';
            }

            purchaseOrderMaster::where('id', $request->id)->update([
                $column1 => NULL,
                $column2 => NULL,
                $column3 => NULL,
            ]);
            foreach ($request['details'] as $key => $detail) {
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    $column1 => NULL,
                    $column2 => NULL,
                    $column3 => NULL
                ]);
            }
            DB::connection('sqlsrv_mmis')->commit();
            return 'success';
        } catch (\Exception $e) {
            DB::connection('sqlsrv_mmis')->commit();
            return $e;
        }
    }

    public function store(Request $request)
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            $authUser = Auth::user();
            $uom = Unitofmeasurement::where('name', 'like', '%Day%')->first();
            foreach ($request->purchase_orders as $purchase_order) {
                if ($purchase_order['po_Document_branch_id'] == 1) {
                    $sequence = SystemSequence::where(['isActive' => true, 'code' => 'PO1', 'branch_id' => $purchase_order['po_Document_branch_id']])->first();
                } else {
                    $sequence = SystemSequence::where(['isActive' => true, 'code' => 'PO7', 'branch_id' => $purchase_order['po_Document_branch_id']])->first();
                }
                $number = str_pad($sequence->seq_no, $sequence->digit, "0", STR_PAD_LEFT);
                $prefix = $sequence->seq_prefix;
                $suffix = $sequence->seq_suffix;
                $checkcanvas = CanvasMaster::where('pr_request_id', $purchase_order['pr_request_id'])->where('vendor_id', $purchase_order['po_Document_vendor_id'])->whereNotNull('canvas_Level2_ApprovedBy')->first();
                // $requestby = PurchaseRequest::where('id',$purchase_order['pr_request_id'])->first();
                // if(!$checkcanvas){

                $checkPO = purchaseOrderMaster::whereNull('comptroller_approved_by')->where('pr_request_id', $purchase_order['pr_request_id'])->where('po_Document_vendor_id', $purchase_order['po_Document_vendor_id'])->first();
                if ($checkPO) {
                    $number = $checkPO->po_Document_number;
                }

                $po_Document_discount_percent = 0;
                $po_Document_discount_amount = 0;
                $po_Document_vat_amount = 0;
                $po_Document_total_net_amount = 0;
                if (sizeof($purchase_order['items']) > 0) {
                    $po_Document_discount_percent = array_sum(array_map(function ($item) {
                        return round($item['recommended_canvas']['canvas_item_discount_percent'], 4);
                    }, $purchase_order['items']));

                    $po_Document_discount_amount = array_sum(array_map(function ($item) {
                        return round($item['recommended_canvas']['canvas_item_discount_amount'], 4);
                    }, $purchase_order['items']));

                    $po_Document_vat_amount = array_sum(array_map(function ($item) {
                        return round($item['recommended_canvas']['canvas_item_vat_amount'], 4);
                    }, $purchase_order['items']));

                    $po_Document_total_net_amount = array_sum(array_map(function ($item) {
                        return round($item['recommended_canvas']['canvas_item_net_amount'], 4);
                    }, $purchase_order['items']));
                }

                $vendor = Vendors::where('id', $purchase_order['po_Document_vendor_id'])->first();
                $po = purchaseOrderMaster::whereNull('comptroller_approved_by')->updateOrCreate(
                    [
                        'pr_request_id' => $purchase_order['pr_request_id'],
                        'po_Document_vendor_id' => $purchase_order['po_Document_vendor_id'],
                    ],
                    [
                        'po_Document_number' => $number,
                        'po_Document_prefix' => $prefix,
                        'po_Document_suffix' => $suffix,
                        'po_Document_branch_id' => (int)$purchase_order['po_Document_branch_id'],
                        'po_Document_warehouse_group_id' => (int)$purchase_order['po_Document_warehouse_group_id'],
                        'po_Document_warehouse_id' =>  (int)$purchase_order['po_Document_warehouse_id'],
                        'po_Document_transaction_date' => Carbon::now(),
                        'po_Document_vendor_id' => (int)$purchase_order['po_Document_vendor_id'],
                        'po_Document_terms_id' => (int)$purchase_order['po_Document_terms_id'],
                        'po_Document_currency_id' => (int)$purchase_order['po_Document_currency_id'],
                        'po_Document_expected_deliverydate' => Carbon::now()->addDays($purchase_order['lead_time']),
                        'po_Document_due_date_unit' => (int)$uom->id,
                        'po_Document_due_date_value' => (int)$purchase_order['lead_time'],
                        'po_Document_overdue_date_value' => 0,
                        'po_Document_total_item_ordered' => sizeof($purchase_order['items']),
                        'po_Document_total_gross_amount' => $purchase_order['po_Document_total_gross_amount'],
                        'po_Document_discount_percent' =>  $po_Document_discount_percent,
                        'po_Document_discount_amount' =>  $po_Document_discount_amount,
                        'po_Document_isvat_inclusive' => $purchase_order['po_Document_isvat_inclusive'],
                        'po_Document_vat_percent' => $purchase_order['po_Document_vat_percent'],
                        'po_Document_vat_amount' => $po_Document_vat_amount,
                        'po_Document_total_net_amount' => $po_Document_total_net_amount,
                        'pr_request_id' => $purchase_order['pr_request_id'],
                        'po_Document_userid' => $request->dietary == 1 ? $checkcanvas->canvas_Document_CanvassBy : $authUser->idnumber,
                        'po_status_id' => 1,
                    ]
                );


                if ($request->dietary == 1 || $checkcanvas->canvas_Warehouse_Id == '36') {
                    $this->autoApproveByComptroller($po->id, $checkcanvas->canvas_Level2_ApprovedBy);
                }
                // update if not exist 
                if (!$checkPO) {
                    $sequence->update([
                        'seq_no' => (int) $sequence->seq_no + 1,
                        'recent_generated' => generateCompleteSequence($prefix, $number, $suffix, ""),
                    ]);
                }
                foreach ($purchase_order['items'] as $item) {

                    $po->details()->updateOrCreate(
                        [
                            'po_Detail_item_id' =>  $item['item_Id'],
                            'pr_detail_id' =>  $item['id'],
                            'canvas_id' =>  $item['recommended_canvas']['id'],
                        ],
                        [
                            'po_Detail_item_id' => $item['item_Id'],
                            'po_detail_currency_id' => $item['recommended_canvas']['currency_id'],
                            'po_Detail_item_listcost' => $item['recommended_canvas']['canvas_item_net_amount'],
                            'po_Detail_item_qty' => $item['recommended_canvas']['canvas_Item_Qty'],
                            'po_Detail_item_unitofmeasurement_id' => $item['recommended_canvas']['canvas_Item_UnitofMeasurement_Id'],
                            'po_Detail_item_discount_percent' => $item['recommended_canvas']['canvas_item_discount_percent'],
                            'po_Detail_item_discount_amount' => $item['recommended_canvas']['canvas_item_discount_amount'],
                            'po_Detail_vat_percent' => $item['recommended_canvas']['canvas_item_vat_rate'],
                            'po_Detail_vat_amount' => $item['recommended_canvas']['canvas_item_vat_amount'],
                            'po_Detail_net_amount' => round($item['recommended_canvas']['canvas_item_net_amount'], 4),
                            'pr_detail_id' => $item['id'],
                            'canvas_id' => $item['recommended_canvas']['id'],
                            'isFreeGoods' => $item['recommended_canvas']['isFreeGoods'],
                        ]
                    );
                    $updatePO = purchaseOrderMaster::where('id', $po['id'])->first();
                    if ($updatePO) {
                        purchaseOrderMaster::where('id', $po['id'])->update([
                            'po_Document_terms_id' => $item['recommended_canvas']['terms_id'] ?? $vendor->id
                        ]);
                    }
                    $checkifconsignment = PurchaseOrderConsignment::where('pr_request_id', $purchase_order['pr_request_id'])->where('vendor_id', $purchase_order['po_Document_vendor_id'])->first();
                    if ($checkifconsignment) {

                        $checkifconsignmentItem = PurchaseOrderConsignmentItem::where('pr_request_id', $purchase_order['pr_request_id'])->where('request_item_id', $item['item_Id'])->first();
                        $checkifconsignment->update([
                            'po_id' => $po['id'],
                            'canvas_id' => $item['recommended_canvas']['id'],
                            'total_gross_amount' => $purchase_order['po_Document_total_gross_amount'],
                            'discount_percent' =>  $po_Document_discount_percent,
                            'discount_amount' =>  $po_Document_discount_amount,
                            'isvat_inclusive' => $purchase_order['po_Document_isvat_inclusive'],
                            'vat_percent' => $purchase_order['po_Document_vat_percent'],
                            'vat_amount' => $po_Document_vat_amount,
                            'total_net_amount' => $po_Document_total_net_amount,
                            'updatedby' => $authUser->idnumber
                        ]);

                        Warehouseitems::where([
                            'branch_id' => $purchase_order['po_Document_branch_id'],
                            'warehouse_Id' => $purchase_order['po_Document_warehouse_id'],
                            'item_Id' =>$item['item_Id'],
                        ])->update([
                            'item_ListCost' => $item['recommended_canvas']['canvas_item_amount'],
                        ]);
                        

                        $checkifconsignmentItem->update([
                            'po_id' => $po['id'],
                            'canvas_id' => $item['recommended_canvas']['id'],
                            'item_listcost' => $item['recommended_canvas']['canvas_item_amount'],
                            'item_qty' => $item['recommended_canvas']['canvas_Item_Qty'],
                            'item_unitofmeasurement_id' => $item['recommended_canvas']['canvas_Item_UnitofMeasurement_Id'],
                            'item_discount_percent' => $item['recommended_canvas']['canvas_item_discount_percent'],
                            'item_discount_amount' => $item['recommended_canvas']['canvas_item_discount_amount'],
                            'vat_percent' => $item['recommended_canvas']['canvas_item_vat_rate'],
                            'vat_amount' => $item['recommended_canvas']['canvas_item_vat_amount'],
                            'total_gross' => $item['recommended_canvas']['canvas_item_total_amount'],
                            'net_amount' => round($item['recommended_canvas']['canvas_item_net_amount'], 4),
                            'updatedby' => $authUser->idnumber
                        ]);
                    }
                }
                $getFreeGoods = CanvasMaster::where('pr_request_id', $purchase_order['pr_request_id'])->where('vendor_id', $purchase_order['po_Document_vendor_id'])->where('isFreeGoods', 1)->get();

                if (count($getFreeGoods) > 0) {
                    $PurchaseOrderDetails = PurchaseOrderDetails::where('po_id', $po->id)->first();
                    foreach ($getFreeGoods as $item) {
                        $po->details()->updateOrCreate(
                            [
                                'pr_detail_id' =>  $item['pr_request_details_id'],
                                'canvas_id' =>  $item['id'],
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
                                'comptroller_approved_by' => $PurchaseOrderDetails->comptroller_approved_by,
                                'comptroller_approved_date' => $PurchaseOrderDetails->comptroller_approved_date,
                                'admin_approved_by' => $PurchaseOrderDetails->admin_approved_by,
                                'admin_approved_date' => $PurchaseOrderDetails->admin_approved_date,
                                'corp_admin_approved_by' => $PurchaseOrderDetails->corp_admin_approved_by,
                                'corp_admin_approved_date' => $PurchaseOrderDetails->corp_admin_approved_date,
                                'ysl_approved_by' => $PurchaseOrderDetails->ysl_approved_by,
                                'ysl_approved_date' => $PurchaseOrderDetails->ysl_approved_date,
                                'isFreeGoods' => $item['isFreeGoods'],
                            ]
                        );
                    }
                }

                // }
            }
            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            return response()->json(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }

    public function updatePOItem(Request $request)
    {

        $pr_request_id = $request->payload['pr_request_id'] ?? '';
        $po_Detail_item_id = $request->payload['po_Detail_item_id'] ?? '';
        $newprice = $request->payload['newprice'] ?? '';
        if (!$pr_request_id)  throw new \Exception('required pr id');
        if (!$po_Detail_item_id)  throw new \Exception('required itemid');
        if (!$newprice)  throw new \Exception('required price');
        DB::connection('sqlsrv_mmis')->update("SET NOCOUNT ON;EXEC RecomputeCanvasAndPurchaseOrderDetails_BaseNewPrice ?,?,?", [$pr_request_id, $po_Detail_item_id, $newprice]);
        return response()->json(['message' => 'Record successfully saved'], 200);
    }
    public function approve(Request $request)
    {
        $user = auth()->user();
        if ($user->role->name == 'comptroller') {
            $this->approveByComptroller($request);
        }
        if ($user->role->name == 'administrator') {
            $this->approvedByAdmin($request);
        }
        if ($user->role->name == 'corporate admin') {
            $this->approvedByCorpAdmin($request);
        }
        if ($user->role->name == 'president') {
            $this->approvedByPresident($request);
        }
        return response()->json(['message' => 'success'], 200);
    }

    private function approveByComptroller($request)
    {
        $isdecline = true;
        foreach ($request['details'] as $key => $detail) {
            $freegoods = PurchaseOrderDetails::where('po_id', $detail['po_id'])->where('isFreeGoods', 1)->first();
            if ($detail['isapproved'] == true) {

                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'comptroller_approved_by' => auth()->user()->idnumber,
                    'comptroller_approved_date' => Carbon::now()
                ]);

                if ($freegoods) {
                    $freegoods->update(
                        [
                            'comptroller_approved_by' => auth()->user()->idnumber,
                            'comptroller_approved_date' => Carbon::now()
                        ]
                    );
                }
                $isdecline = false;
            } else {
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'comptroller_cancelled_by' => auth()->user()->idnumber,
                    'comptroller_cancelled_date' => Carbon::now(),
                    'comptroller_cancelled_remarks' => $request->remarks
                ]);

                if ($freegoods) {
                    $freegoods->update(
                        [
                            'comptroller_cancelled_by' => auth()->user()->idnumber,
                            'comptroller_cancelled_date' => Carbon::now(),
                            'comptroller_cancelled_remarks' => $request->remarks
                        ]
                    );
                }
            }
        }
        if ($isdecline) {
            purchaseOrderMaster::where('id', $request['id'])->update([
                'comptroller_cancelled_by' => auth()->user()->idnumber,
                'comptroller_cancelled_date' => Carbon::now(),
                'comptroller_cancelled_remarks' =>  $request->remarks
            ]);
        } else {
            purchaseOrderMaster::where('id', $request['id'])->update([
                'comptroller_approved_by' => auth()->user()->idnumber,
                'comptroller_approved_date' => Carbon::now(),
            ]);
        }
    }



    private function autoApproveByComptroller($id, $compid)
    {
        DB::connection('sqlsrv_mmis')->beginTransaction();
        try {
            purchaseOrderMaster::where('id', $id)->update([
                'comptroller_approved_by' => $compid,
                'comptroller_approved_date' => Carbon::now(),
            ]);
            PurchaseOrderDetails::where('po_id', $id)->update([
                'comptroller_approved_by' => $compid,
                'comptroller_approved_date' => Carbon::now()
            ]);
            DB::connection('sqlsrv_mmis')->commit();
        } catch (\Exception $e) {
            DB::connection('sqlsrv_mmis')->rollback();
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }


    private function approvedByAdmin($request)
    {
        $isdecline = true;
        foreach ($request['details'] as $key => $detail) {
            $freegoods = PurchaseOrderDetails::where('po_id', $detail['po_id'])->where('isFreeGoods', 1)->first();
            if ($detail['isapproved'] == true) {
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'admin_approved_by' => Auth()->user()->idnumber,
                    'admin_approved_date' => Carbon::now()
                ]);

                if ($freegoods) {
                    $freegoods->update(
                        [
                            'admin_approved_by' => Auth()->user()->idnumber,
                            'admin_approved_date' => Carbon::now()
                        ]
                    );
                }
                $isdecline = false;
            } else {
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'admin_cancelled_by' => Auth()->user()->idnumber,
                    'admin_cancelled_date' => Carbon::now(),
                    'admin_cancelled_remarks' => $request->remarks
                ]);
                if ($freegoods) {
                    $freegoods->update(
                        [
                            'admin_cancelled_by' => auth()->user()->idnumber,
                            'admin_cancelled_date' => Carbon::now(),
                            'admin_cancelled_remarks' => $request->remarks
                        ]
                    );
                }
            }
        }
        if ($isdecline) {
            purchaseOrderMaster::where('id', $request['id'])->update([
                'admin_cancelled_by' => auth()->user()->idnumber,
                'admin_cancelled_date' => Carbon::now(),
                'admin_cancelled_remarks' =>  $request->remarks
            ]);
        } else {
            purchaseOrderMaster::where('id', $request['id'])->update([
                'admin_approved_by' => auth()->user()->idnumber,
                'admin_approved_date' => Carbon::now(),
            ]);
        }
    }

    private function approvedByCorpAdmin($request)
    {
        $isdecline = true;
        foreach ($request['details'] as $key => $detail) {
            $freegoods = PurchaseOrderDetails::where('po_id', $detail['po_id'])->where('isFreeGoods', 1)->first();
            if ($detail['isapproved'] == true) {
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'corp_admin_approved_by' => auth()->user()->idnumber,
                    'corp_admin_approved_date' => Carbon::now()
                ]);
                if ($freegoods) {
                    $freegoods->update(
                        [
                            'corp_admin_approved_by' => auth()->user()->idnumber,
                            'corp_admin_approved_date' => Carbon::now()
                        ]
                    );
                }

                $isdecline = false;
            } else {
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'corp_admin_cancelled_by' => auth()->user()->idnumber,
                    'corp_admin_cancelled_date' => Carbon::now(),
                    'corp_admin_cancelled_remarks' => $request->remarks
                ]);
                if ($freegoods) {
                    $freegoods->update(
                        [
                            'corp_admin_cancelled_by' => auth()->user()->idnumber,
                            'corp_admin_cancelled_date' => Carbon::now(),
                            'corp_admin_cancelled_remarks' => $request->remarks
                        ]
                    );
                }
            }
        }
        if ($isdecline) {
            purchaseOrderMaster::where('id', $request['id'])->update([
                'corp_admin_cancelled_by' => auth()->user()->idnumber,
                'corp_admin_cancelled_date' => Carbon::now(),
                'corp_admin_cancelled_remarks' =>  $request->remarks
            ]);
        } else {
            purchaseOrderMaster::where('id', $request['id'])->update([
                'corp_admin_approved_by' => auth()->user()->idnumber,
                'corp_admin_approved_date' => Carbon::now(),
            ]);
        }
    }

    private function approvedByPresident($request)
    {
        $isdecline = true;
        foreach ($request['details'] as $key => $detail) {
            $freegoods = PurchaseOrderDetails::where('po_id', $detail['po_id'])->where('isFreeGoods', 1)->first();
            if ($detail['isapproved'] == true) {
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'ysl_approved_by' => auth()->user()->idnumber,
                    'ysl_approved_date' => Carbon::now()
                ]);

                if ($freegoods) {
                    $freegoods->update(
                        [
                            'ysl_approved_by' => auth()->user()->idnumber,
                            'ysl_approved_date' => Carbon::now()
                        ]
                    );
                }
                $isdecline = false;
            } else {
                PurchaseOrderDetails::where('id', $detail['id'])->update([
                    'ysl_cancelled_by' => auth()->user()->idnumber,
                    'ysl_cancelled_date' => Carbon::now(),
                    'ysl_cancelled_remarks' => $request->remarks
                ]);

                if ($freegoods) {
                    $freegoods->update(
                        [
                            'ysl_cancelled_by' => auth()->user()->idnumber,
                            'ysl_cancelled_date' => Carbon::now(),
                            'ysl_cancelled_remarks' => $request->remarks
                        ]
                    );
                }
            }
        }
        if ($isdecline) {
            purchaseOrderMaster::where('id', $request['id'])->update([
                'ysl_cancelled_by' => auth()->user()->idnumber,
                'ysl_cancelled_date' => Carbon::now(),
                'ysl_cancelled_remarks' =>  $request->remarks
            ]);
        } else {
            purchaseOrderMaster::where('id', $request['id'])->update([
                'ysl_approved_by' => auth()->user()->idnumber,
                'ysl_approved_date' => Carbon::now(),
            ]);
        }
    }

    public function destroy() {}
}
