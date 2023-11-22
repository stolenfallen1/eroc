<?php

namespace App\Helpers\SearchFilter\Procurements;

use App\Models\MMIS\procurement\purchaseOrderMaster;
use Carbon\Carbon;

class PurchaseOrders
{
  protected $model;
  protected $authUser;
  public function __construct()
  {
    $this->model = purchaseOrderMaster::query();
    $this->authUser = auth()->user();
  }

  public function searchable(){
    $this->model->with('details.canvas', 'purchaseRequest', 'vendor', 'warehouse', 'status', 'user');
    $this->searchableColumns();
    $this->byBranch();
    $this->byItemGroup();
    $this->byDepartment();
    $this->byVendor();
    $this->byStartDate();
    $this->byEndDate();
    $this->byTab();
    $this->byUser();
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function searchableColumns(){
    $searchable = ['po_number', 'pr_number'];
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where(function ($q) use ($keyword, $searchable) {
          foreach ($searchable as $column) {
              if ($column == 'po_number')
                  $q->whereRaw("CONCAT(po_Document_prefix,'',po_Document_number,'',po_Document_suffix) = ?", $keyword)
                  ->orWhere('po_Document_number', 'LIKE' , '%' . $keyword . '%');
                  // $q->where('pr_Document_Number', 'LIKE' , '%' . $keyword . '%');
              else if($column == 'pr_number'){
                $q->orWhereHas('purchaseRequest', function($q2) use($keyword){
                  $q2->whereRaw("CONCAT(pr_Document_Prefix,'',pr_Document_Number,'',pr_Document_Suffix) = ?", $keyword)
                  ->orWhere('pr_Document_Number', 'LIKE' , '%' . $keyword . '%');
                });
              }
          }
      });
    }
  }
  
  private function byBranch(){
    if($this->authUser->branch_id == 1)
    {
      $this->model->where('po_Document_branch_id', Request()->branch);
    }else{
      $this->model->where('po_Document_branch_id', $this->authUser->branch_id);
    }
  }

  private function byDepartment(){
    if(Request()->department){
      $this->model->where('po_Document_warehouse_id', Request()->department);
    }
  }
  
  private function byItemGroup()
  {
    if(Request()->item_group){
      $this->model->whereHas('purchaseRequest', function($q){
        $q->where('invgroup_id', Request()->item_group);
      });
    }
  }
  
  private function byVendor(){
    if(Request()->supplier){
      $this->model->where('po_Document_vendor_id', Request()->supplier);
    }
  }

  private function byStartDate(){
    if(Request()->start_date){
      $this->model->whereDate('po_Document_transaction_date', '>=', Carbon::parse(Request()->start_date));
    }
  }

  private function byEndDate(){
    if(Request()->end_date){
      $this->model->whereDate('po_Document_transaction_date', '<=', Carbon::parse(Request()->end_date));
    }
  }

  private function byTab(){
    if(Request()->tab == 1){
      $this->forApproval();
    }
    else if (Request()->tab == 2){
      $this->forComptroller();
    }
    else if (Request()->tab == 3){
      $this->forAdministrator();
    }
    else if (Request()->tab == 4){
      $this->forCorpAdmin();
    }
    else if (Request()->tab == 5){
      $this->forPresident();
    }
  }

  private function byUser(){
    if($this->authUser->role->name == 'staff' || $this->authUser->role->name == 'department head'){
      $this->model->where('po_Document_branch_id', $this->authUser->branch_id)->whereIn('po_Document_warehouse_id', $this->authUser->departments);
    }
  }

  private function forApproval(){
    if(Request()->branch == 1){
      if($this->authUser->role->name == 'comptroller'){
        $this->model->where(['comptroller_approved_date' => NULL, 'comptroller_cancelled_date' => NULL]);
      }
      else if($this->authUser->role->name == 'administrator'){
        $this->model->whereNotNull('comptroller_approved_date')
        ->where(function($q){
          $q->whereNull('corp_admin_approved_date')->orWhereNull('corp_admin_cancelled_date');
        })
        ->where(['admin_approved_date' => null, 'admin_cancelled_date' => null]);
      }
    }else{
      if($this->authUser->role->name == 'comptroller'){
        $this->model->where(function($q){
          $q->whereHas('purchaseRequest', function($q1){
            $q1->where('invgroup_id', 2);
          })->where(['comptroller_approved_date' => NULL, 'comptroller_cancelled_date' => NULL]);
        })->orWhere(function($q){
          $q->whereHas('purchaseRequest', function($q1){
            $q1->where('invgroup_id', '!=', 2);
          })->where('admin_approved_date', '!=', null)->where(['comptroller_approved_date' => null, 'comptroller_cancelled_date' => null]);
        });
        // $this->model->where('admin_approved_date', '!=', null)->where(['comptroller_approved_date' => null, 'comptroller_cancelled_date' => null]);
      }
      else if($this->authUser->role->name == 'administrator'){

        $this->model->where(['admin_approved_date' => null, 'admin_cancelled_date' => null])->where('po_Document_branch_id', $this->authUser->branch_id);
      }
      
    }
    if($this->authUser->role->name == 'corporate admin'){
      $this->model->where(function($q1){
        $q1->where('admin_approved_date', null)->where('comptroller_approved_date', '!=', null)
        ->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null])
        ->whereHas('purchaseRequest', function($q2){
          $q2->where('invgroup_id', 2);
        });
      })->orWhere(function($q1){
        $q1->where('admin_approved_date', null)->where('comptroller_approved_date', '!=', null)
        ->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null])
        ->whereHas('purchaseRequest', function($q2){
          $q2->where('invgroup_id', '!=', 2);
        });
      });

      // $this->model->where('comptroller_approved_date', '!=', null)->where('admin_approved_date', '!=', null)
      //   ->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null]);
    }
    else if($this->authUser->role->name == 'president'){
      if(Request()->branch == 1){

        $this->model->where(function($q){
          $q->whereNotNull('corp_admin_approved_date')->orWhereNotNull('admin_approved_date');
        })
        ->where(['ysl_approved_date' => null, 'ysl_cancelled_date' => null])
        ->where('po_Document_total_net_amount', '>', 99999);

      }else{

        $this->model->where('corp_admin_approved_date', '!=', null)
        ->where(['ysl_approved_date' => null, 'ysl_cancelled_date' => null])
        ->where('po_Document_total_net_amount', '>', 99999);

      }
    }

    $this->model->orderBy('created_at', 'desc');
  }

  private function forComptroller(){
    $this->model->where('comptroller_approved_date', '!=', null);
    $this->model->orderBy('created_at', 'desc');
  }

  private function forAdministrator(){
    $this->model->where('admin_approved_date', '!=', null);
    $this->model->orderBy('created_at', 'desc');
  }

  private function forCorpAdmin(){
    $this->model->where('corp_admin_approved_date', '!=', null);
    $this->model->orderBy('created_at', 'desc');
  }

  private function forPresident(){
    $this->model->where('ysl_approved_date', '!=', null);
    $this->model->orderBy('created_at', 'desc');
  }

}