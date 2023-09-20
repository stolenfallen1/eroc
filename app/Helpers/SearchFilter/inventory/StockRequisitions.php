<?php

namespace App\Helpers\SearchFilter\inventory;

use App\Models\MMIS\inventory\StockRequisition;
use App\Models\MMIS\inventory\StockTransfer;
use Illuminate\Support\Facades\Auth;

class StockRequisitions
{
  protected $model;
  protected $authUser;

  public function __construct()
  {
    $this->model = StockRequisition::query();
    $this->authUser = auth()->user();
  }

  public function searchable()
  {

    $this->model->with('requestedBy', 'requesterWarehouse', 'requesterBranch', 'senderWarehouse', 'senderBranch', 'transferBy', 'category', 'receivedBy', 'items.item.wareHouseItem');

    $this->byTab();
    $this->byWarehouse();

    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  private function byWarehouse()
  {
    $auth_user = Auth::user();
    if(Request()->tab == 5){
      $this->model->where(function($q) use($auth_user){
        $q->where('requester_warehouse_id', $auth_user->warehouse_id)->orWhere('sender_warehouse_id', $auth_user->warehouse_id);
      })->where(function($q) use($auth_user){
        $q->where('requester_branch_id', $auth_user->branch_id)->orWhere('sender_branch_id', $auth_user->branch_id);
      });
    }
  }

  private function byTab()
  {
    if (Request()->tab == 1) {
      $this->forApproval();
    }elseif(Request()->tab == 2){
      $this->forDepartmentHead();
    }elseif (Request()->tab == 3) {
      $this->forAdministrator();
    }elseif (Request()->tab == 4) {
      $this->forCoporateAdmin();
    }elseif (Request()->tab == 5) {
      $this->forReleasing();
    }elseif (Request()->tab == 6) {
      $this->forReceiving();
    }elseif (Request()->tab == 7) {
      $this->forReceived();
    }
  }

  private function forApproval(){
    if($this->authUser->role->name == 'department head'){
      $this->model->whereHas('items', function($q1){
        $q1->where(['department_head_declined_by' => null, 'department_head_approved_by' => null]);
      })->where(['sender_warehouse_id' => $this->authUser->warehouse_id, 'requester_branch_id' => $this->authUser->branch_id]);
    }elseif ($this->authUser->role->name == 'administrator') {
      $this->model->whereHas('items', function($q1){
        $q1->whereNotNull('department_head_approved_by')->where(['administrator_approved_by' => null, 'administrator_declined_by' => null]);
      });
    }elseif ($this->authUser->role->name == 'corporate admin') {
      $this->model->where('is_inter_branch', 1)
      ->whereHas('items', function($q1){
        $q1->whereNotNull('administrator_approved_by')->where(['corporate_admin_approved_by' => null, 'corporate_admin_declined_by' => null]);
      });
    }
  }
  private function forDepartmentHead(){
    if(Request()->tab == 2){
      $this->model->whereHas('items', function($q1){
        $q1->whereNotNull('department_head_approved_by');
      });
    }
  }

  private function forAdministrator(){
    if(Request()->tab == 3){
      $this->model->whereHas('items', function($q1){
        $q1->whereNotNull('administrator_approved_by');
      });
    }
  }

  private function forCoporateAdmin(){
    if(Request()->tab == 4){
      $this->model->whereHas('items', function($q1){
        $q1->whereNotNull('corporate_admin_approved_by');
      });
    }
  }

  private function forReleasing(){
    if(Request()->tab == 5){
      $this->model->whereHas('items', function($q1){
        $q1->whereNotNull('corporate_admin_approved_by');
      })->whereNull('transfer_by_id');
    }
  }

  private function forReceiving(){
    if(Request()->tab == 6){
      $this->model->whereHas('items', function($q1){
        $q1->whereNotNull('corporate_admin_approved_by');
      })->whereNotNull('transfer_by_id');
    }
  }

  private function forReceived(){
    if(Request()->tab == 7){
      $this->model->whereNotNull('transfer_by_id');
    }
  }
}
