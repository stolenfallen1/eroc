<?php

namespace App\Helpers\SearchFilter\inventory;

use Carbon\Carbon;
use App\Models\MMIS\Audit;
use App\Models\MMIS\inventory\Consignment;
use App\Models\MMIS\inventory\PurchaseOrderConsignment;

class PurchaseOrderConsignments
{
  protected $model;
  protected $authUser;
  public function __construct()
  {
    $this->model = PurchaseOrderConsignment::query();
    $this->authUser = auth()->user();
  }

  public function searchable()
  {
    $this->model->with(
      'rr_consignment_master',
      'purchaseRequest',
      'purchaseOrder',
      'vendor',
      'items',
      'items.itemdetails',
      'items.unit',
      'items.batchs',
      'receiver'
    );
    $this->byTab();
    $this->searcColumns();
    $this->isApproved();
    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function auditsearchable()
  {
    $this->model->whereNull('isaudit');
    $this->model->with(
      'rr_consignment_master',
      'purchaseRequest',
      'purchaseOrder',
      'vendor',
      'items',
      'items.itemdetails',
      'items.unit',
      'items.batchs',
      'receiver',
      'purchaseRequest.warehouse',
      'purchaseRequest.status',
      'purchaseRequest.category',
      'purchaseRequest.subcategory',
      'purchaseRequest.purchaseRequestAttachments',
      'purchaseRequest.user',
      'rr_consignment_master.items',
      'rr_consignment_master.items.item',
      'rr_consignment_master.items.unit',
      'purchaseRequest.itemGroup',
      'consignmentPr',
      'consignmentPr.items',
      'consignmentPr.items.itemdetails',
      'consignmentPr.items.unit',
      'consignmentPo',
      'consignmentPo.items',
      'consignmentPo.items.itemdetails',
      'consignmentPo.items.unit'
    );
    $this->byTab();
    $this->searcColumns();
    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function auditedsearchable()
  {
    $this->model->where('isaudit', 1);
    $this->model->with(
      'rr_consignment_master',
      'purchaseRequest',
      'purchaseOrder',
      'vendor',
      'items',
      'items.itemdetails',
      'items.unit',
      'items.batchs',
      'receiver',
      'purchaseRequest.warehouse',
      'purchaseRequest.status',
      'purchaseRequest.category',
      'purchaseRequest.subcategory',
      'purchaseRequest.purchaseRequestAttachments',
      'purchaseRequest.user',
      'rr_consignment_master.items',
      'rr_consignment_master.items.item',
      'rr_consignment_master.items.unit',
      'purchaseRequest.itemGroup',
      'consignmentPr',
      'consignmentPr.items',
      'consignmentPr.items.itemdetails',
      'consignmentPr.items.unit',
      'consignmentPo',
      'consignmentPo.items',
      'consignmentPo.items.itemdetails',
      'consignmentPo.items.unit',
      'auditConsignment',
      'auditConsignment.user',
      'consignmentPo.auditConsignment',
      'consignmentPo.auditConsignment.user',
    );
    $this->byTab();
    $this->searcColumns();
    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }
  public function searcColumns()
  {
    $searchable = ['invoice', 'rr_number', 'dr_number'];
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      // $this->model->where('rr_Document_Number', 'LIKE' , '%'.$keyword.'%' );

     $this->model->where(function ($query) use ($keyword, $searchable) {
        foreach ($searchable as $column) {
          if ($column === 'rr_number') {
            $query->orWhereHas('rr_consignment_master', function ($q2) use ($keyword) {
              $q2->where('rr_Document_Number', 'LIKE', '%' . $keyword . '%');
            });
          } elseif ($column === 'invoice') {
            $query->orWhere('invoice_no', 'LIKE', '%' . $keyword . '%');
          } elseif ($column === 'dr_number') {
            $query->orWhereHas('rr_consignment_master', function ($q2) use ($keyword) {
              $q2->where('rr_Document_Delivery_Receipt_No', 'LIKE', '%' . $keyword . '%');
            });
          }
        }
      });
    }
  }

  public function isApproved()
  {
    $this->model->where(function ($query) {
      $query->whereHas('purchaseOrder', function ($q2) {
        $q2->where('comptroller_approved_date', '!=', null)->where('admin_approved_date', '!=', null);
      });
    });
  }
 
  public function byTab()
  {
    if (Request()->tab == 3) {
      $this->model->whereNull('receivedstatus');
    } else if (Request()->tab == 4) {
      $this->model->whereNull('invoice_no');
    } else if (Request()->tab == 5) {
      $this->model->whereNotNull('invoice_no');
    }
  }
}
