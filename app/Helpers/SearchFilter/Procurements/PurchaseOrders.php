<?php

namespace App\Helpers\SearchFilter\Procurements;

use Carbon\Carbon;
use App\Helpers\ParentRole;
use App\Models\MMIS\procurement\purchaseOrderMaster;

class PurchaseOrders
{
  protected $model;
  protected $authUser;
  protected $role;
  public function __construct()
  {
    $this->model = purchaseOrderMaster::query();
    $this->authUser = auth()->user();  
    $this->role = new ParentRole();
  }

  public function searchable(){
    if($this->role->purchaser()){
      $this->model->whereIn('po_Document_warehouse_id', $this->authUser->departments);
    }
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
    $this->model->whereNotIn('po_status_id',['4']);
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function searchableColumns(){
    $searchable = ['po_number', 'pr_number'];
    $this->model->where(function($query) {
      $query->whereYear('created_at', '!=', 2022);
    });
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where(function ($q) use ($keyword, $searchable) {
          foreach ($searchable as $column) {
              if ($column == 'po_number'){
                $q->whereRaw("CONCAT(po_Document_prefix,'',po_Document_number,'',po_Document_suffix) = ?", $keyword)
                ->orWhere('po_Document_number', 'LIKE' , '%' . $keyword . '%');
              }
              else if($column == 'pr_number'){
                $q->orWhereHas('purchaseRequest', function($q2) use($keyword){
                  $q2->whereRaw("CONCAT(pr_Document_Prefix,'',pr_Document_Number,'',pr_Document_Suffix) = ?", $keyword)
                  ->orWhere('pr_Document_Number', 'LIKE' , '%' . $keyword . '%');
                });
              }
                  // $q->where('pr_Document_Number', 'LIKE' , '%' . $keyword . '%');
          }
      });
    }else{
      // $this->model->where('po_Document_number', 'like', "000%");
    }
  }
  
  private function byBranch(){
    if($this->authUser->branch_id == 1)
    {
      if($this->role->staff() || $this->role->department_head() || $this->role->purchaser()){
        $branch =  Request()->branch ? Request()->branch : $this->authUser->branch_id;
        $this->model->where('po_Document_branch_id',$branch);
      }
      else{
        $this->model->where('po_Document_branch_id',Request()->branch);
      }
    }else{
      $this->model->where('po_Document_branch_id', $this->authUser->branch_id);
    }
  }

  private function byDepartment(){
    if(Request()->department){
      if($this->role->staff() || $this->role->department_head()){
        // $this->model->where('po_Document_warehouse_id',$this->authUser->warehouse_id);
        $this->model->whereIn('po_Document_warehouse_id', $this->authUser->departments);
      }else{
        $this->model->where('po_Document_warehouse_id', Request()->department);
      }
    }else{
      if($this->role->staff() || $this->role->department_head()){
        // $this->model->where('po_Document_warehouse_id',$this->authUser->warehouse_id);
        
        $this->model->whereIn('po_Document_warehouse_id', $this->authUser->departments);
      }
    }
    // $role_name = ['comptroller', 'administrator','president','corporate admin','purchaser'];
    // if (!in_array($this->authUser->role->name, $role_name, true)) {
    //   if(Request()->department){
    //     $this->model->where('po_Document_warehouse_id', Request()->department);
    //   }
    // }else{
    //   if(Request()->department){
    //     if($this->authUser->role->warehouse_id != Request()->department){
    //       $this->model->where('po_Document_warehouse_id', Request()->department);
    //     }
    //   }
    // }
    
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
    else if (Request()->tab == 6){
      $this->forDeclined();
    }
  }

  private function byUser(){
    if($this->role->staff() || $this->role->department_head()){
      $this->model->where('po_Document_branch_id', $this->authUser->branch_id)->whereIn('po_Document_warehouse_id', $this->authUser->departments);
    }
  }

  private function forApproval(){
    
    if(Request()->branch == 1 && $this->authUser->branch_id == 1){
      if($this->role->comptroller()){
        $this->model->where(['comptroller_approved_date' => NULL, 'comptroller_cancelled_date' => NULL]);
      }
      else if($this->role->administrator()){
        $this->model->whereNotNull('comptroller_approved_date')
        ->where(function($q){
          $q->whereNull('corp_admin_approved_date')->orWhereNull('corp_admin_cancelled_date');
        })
        ->where(['admin_approved_date' => null, 'admin_cancelled_date' => null]);
      }else if($this->role->purchaser()){
        if(Request()->approver == 1){
          $this->model->where(['comptroller_approved_date' => NULL, 'comptroller_cancelled_date' => NULL]);
        }else if(Request()->approver == 2){
          $this->model->whereNotNull('comptroller_approved_date')->where(['corp_admin_approved_date' => null])
          ->where(['admin_approved_date' => null, 'admin_cancelled_date' => null]);
        }else if(Request()->approver == 3){
          $this->model->where('comptroller_approved_date', '!=', null)->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null]);
        }else if(Request()->approver == 4){
          $this->model->where(function($q){
            $q->whereNotNull('corp_admin_approved_date')->orWhereNotNull('admin_approved_date');
          })
          // $this->model->where('comptroller_approved_date', '!=', null)->where('corp_admin_approved_date', '!=', null)
          ->where(['ysl_approved_date' => null, 'ysl_cancelled_date' => null])
          ->where('po_Document_total_net_amount', '>', 99999);
        }
        else{
          $this->model->whereNull('comptroller_approved_by')->where(function($q){
            $q->whereNull('admin_approved_by')->whereNull('corp_admin_approved_by');
          });
        }

        $this->model->orderBy('isprinted', 'asc');
      }else {
        if(!$this->role->comptroller() && !$this->role->administrator() && 
          !$this->role->corp_admin() && !$this->role->president() ){
          $this->model->whereNull('comptroller_approved_by')->where(function($q){
            $q->whereNull('admin_approved_by')->whereNull('corp_admin_approved_by');
          });
        }
        $this->model->orderBy('isprinted', 'asc');
      }
      
    }else{
      if($this->role->comptroller()){
        $this->model->where('admin_approved_date', '!=', null);
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
      else if($this->role->administrator()){
        $this->model->where(['admin_approved_date' => null, 'admin_cancelled_date' => null])->where('po_Document_branch_id', $this->authUser->branch_id);
      }
      else if($this->role->purchaser()){

        if(Request()->approver == 1){
          $this->model->where('admin_approved_date', '!=', null)->where(['comptroller_approved_date' => NULL, 'comptroller_cancelled_date' => NULL]);
        }else if(Request()->approver == 2){
          $this->model->where('admin_approved_date', '=', null)->where(['comptroller_approved_date' => null, 'comptroller_approved_by' => null]);
        }else if(Request()->approver == 3){
          $this->model->where('admin_approved_date', '!=', null)->where('comptroller_approved_date', '!=', null)->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null]);
        }else if(Request()->approver == 4){
          $this->model->where(function($q){
            $q->whereNotNull('corp_admin_approved_date')->orWhereNotNull('admin_approved_date');
          })
          // $this->model->where('comptroller_approved_date', '!=', null)->where('corp_admin_approved_date', '!=', null)
          ->where(['ysl_approved_date' => null, 'ysl_cancelled_date' => null])
          ->where('po_Document_total_net_amount', '>', 99999);
        }
        else{
          $this->model->whereNull('comptroller_approved_by')->where(function($q){
            $q->whereNull('admin_approved_by')->whereNull('corp_admin_approved_by');
          });
        }

        $this->model->orderBy('isprinted', 'asc');
      }
      else{
        $this->model->whereNull('corp_admin_approved_by');
      }
      
    }

    if($this->role->corp_admin()){
      if($this->authUser->branch_id == Request()->branch){
        $this->model->where('admin_approved_date', null)->where('comptroller_approved_date', '!=', null)->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null]);
      }else{
         $this->model->where('admin_approved_date', '!=', null)->where('comptroller_approved_date', '!=', null)->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null]);
      }
      // $this->model->where(function($q1){
      //   $q1->where('admin_approved_date', null)->where('comptroller_approved_date', '!=', null)
      //   ->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null])
      //   ->whereHas('purchaseRequest', function($q2){
      //     $q2->where('invgroup_id', 2);
      //   });
      // })->orWhere(function($q1){
      //   $q1->where('admin_approved_date', null)->where('comptroller_approved_date', '!=', null)
      //   ->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null])
      //   ->whereHas('purchaseRequest', function($q2){
      //     $q2->where('invgroup_id', '!=', 2);
      //   });
      // });

      // $this->model->where('comptroller_approved_date', '!=', null)->where('admin_approved_date', '!=', null)
      //   ->where(['corp_admin_approved_date' => null, 'corp_admin_cancelled_date' => null]);
    }
    else if($this->role->president()){
      if(Request()->branch == 1){

        $this->model
        ->where(function($q){
          $q->whereNotNull('corp_admin_approved_date')->orWhereNotNull('admin_approved_date');
        })
        ->where(function($query) {
            $query->where('comptroller_approved_date', '!=', null)->where('admin_approved_date', '!=', null)->where(['ysl_approved_date' => null, 'ysl_cancelled_date' => null])->where('po_Document_currency_id', 2)->where('po_Document_total_net_amount', '>', 2000);
        })
        ->orWhere(function($query) {
            $query->where('comptroller_approved_date', '!=', null)->where('admin_approved_date', '!=', null)->where(['ysl_approved_date' => null, 'ysl_cancelled_date' => null])->where('po_Document_currency_id', 1)->where('po_Document_total_net_amount', '>', 99999);
        })
        
        ->where(['ysl_approved_date' => null, 'ysl_cancelled_date' => null]);
        // ->where('po_Document_total_net_amount', '>', 99999)->where('po_Document_total_net_amount', '>', 2000)->where(function($q){
        //   $q->where('currency_id')->orWhere('currency_id');
        // });

      }else{

        $this->model->where('corp_admin_approved_date', '!=', null)
        ->where(['ysl_approved_date' => null, 'ysl_cancelled_date' => null])
        ->where('po_Document_total_net_amount', '>', 99999);

      }
    }
  }

  private function forComptroller(){
    $this->model->where('comptroller_approved_date', '!=', null);
    // $this->model->orderBy('created_at', 'desc');
    $this->model->orderBy('isprinted', 'desc');
  }

  private function forAdministrator(){
    $this->model->where('comptroller_approved_date', '!=', null);
    $this->model->where('admin_approved_date', '!=', null);
    $this->model->orderBy('isprinted', 'asc');
    $this->model->orderBy('created_at', 'desc');
  }

  private function forCorpAdmin(){
    $this->model->where('comptroller_approved_date', '!=', null);
    $this->model->where('corp_admin_approved_date', '!=', null);
    $this->model->orderBy('isprinted', 'asc');
    $this->model->orderBy('created_at', 'desc');
  }

  private function forPresident(){
   
    $this->model->where('comptroller_approved_date', '!=', null)->where('admin_approved_date', '!=', null)
      ->where(function($q){
        $q->whereNotNull('corp_admin_approved_date')->orWhereNotNull('admin_approved_date');
    });
    $this->model->where('ysl_approved_date', '!=', null);
    $this->model->orderBy('isprinted', 'asc');
    $this->model->orderBy('created_at', 'desc');
  }

  private function forDeclined(){
    if($this->role->president()){
      $this->model->whereHas('details', function($q){
        $q->whereNotNull('ysl_cancelled_by');
      });
    }
    else if($this->role->corp_admin()){
      $this->model->whereHas('details', function($q){
        $q->whereNotNull('corp_admin_cancelled_by');
      });
    }
    else if($this->role->administrator()){
      $this->model->whereHas('details', function($q){
        $q->whereNotNull('admin_cancelled_by');
      });
    }
    else if($this->role->comptroller()){
      $this->model->whereHas('details', function($q){
        $q->whereNotNull('comptroller_cancelled_by');
      });
    }else{
      $this->model->whereHas('details', function($q){
        if($this->role->staff() || $this->role->department_head()){
          $q->where('po_Document_warehouse_id', $this->authUser->warehouse_id)->whereNotNull('comptroller_cancelled_by')->orWhereNotNull('admin_cancelled_by')
          ->orWhereNotNull('corp_admin_cancelled_by')->orWhereNotNull('ysl_cancelled_by');
        }else{

          $q->whereNotNull('comptroller_cancelled_by')->orWhereNotNull('admin_cancelled_by')
          ->orWhereNotNull('corp_admin_cancelled_by')->orWhereNotNull('ysl_cancelled_by');
        }
      });
    }
    $this->model->orderBy('isprinted', 'asc');
    $this->model->orderBy('created_at', 'desc');
  }
}