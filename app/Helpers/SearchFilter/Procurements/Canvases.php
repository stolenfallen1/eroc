<?php

namespace App\Helpers\SearchFilter\Procurements;

use Carbon\Carbon;
use App\Helpers\ParentRole;
use App\Models\MMIS\procurement\CanvasMaster;
use App\Models\MMIS\procurement\PurchaseRequest;

class Canvases
{
  protected $model;
  protected $authUser;
  protected $role;

  public function __construct()
  {
    $this->model = CanvasMaster::query();
    $this->authUser = auth()->user();
    $this->role = new ParentRole();
  }

  public function searchable(){
    $this->model->with('vendor', 'unit', 'attachments','item');
    $this->model->where('pr_request_details_id', Request()->details_id);
    $this->byBranch();
    $this->byTab();
    $this->byItemGroup();
    $this->byCategory();
    $this->bySubCategory();
    $this->byPriority();
    $this->byRequestedDate();
    $this->byRequiredDate();
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }
  
  private function byBranch(){
    if($this->role->department_head() || $this->role->staff()  ||  $this->role->consultant() || $this->role->administrator() || $this->authUser->role->name == '')
    {
      $this->model->where('canvas_Branch_Id', $this->authUser->branch_id);
    }
  }

  private function byItemGroup()
  {
    if(Request()->item_group){
      $this->model->where('invgroup_id', Request()->item_group);
    }
  }

  private function byCategory(){
    if(Request()->category){
      $this->model->where('item_Category_Id', Request()->category);
    }
  }

  private function bySubCategory(){
    if(Request()->subcategory){
      $this->model->where('item_SubCategory_Id', Request()->subcategory);
    }
  }
  
  private function byPriority(){
    if(Request()->priority){
      $this->model->where('pr_Priority_Id', Request()->priority);
    }
  }

  private function byRequestedDate(){
    if(Request()->requested_date){
      $this->model->whereDate('pr_Transaction_Date', Carbon::parse(Request()->requested_date));
    }
  }

  private function byRequiredDate(){
    if(Request()->required_date){
      $this->model->whereDate('pr_Transaction_Date_Required', Carbon::parse(Request()->required_date));
    }
  }

  private function byTab(){
    if(Request()->tab == 1){
      $this->forApproval();
    }
    else if (Request()->tab == 2){
      $this->forDepartmentHead();
    }
    else if (Request()->tab == 3){
      $this->forConsultant();
    }
    else if (Request()->tab == 4){
      $this->forAdministrator();
    }
    else if (Request()->tab == 5){
      $this->forCanvas();
    }
  }

  private function forApproval(){
    if($this->role->department_head() || $this->role->staff() ){

      $this->model->where('warehouse_Id', $this->authUser->warehouse_id)
      ->where(['pr_DepartmentHead_ApprovedBy' => null, 'pr_DepartmentHead_CancelledBy' => null]);

      $this->model->with('purchaseRequestDetails.itemMaster');

    }else if( $this->role->administrator() ){
      
      $this->model->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])
      ->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where('invgroup_id', '!=', 2);

      $this->model->with(['purchaseRequestDetails'=>function ($q){
        $q->with('itemMaster')->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      }]);

    }else if( $this->role->consultant() ){

      $this->model->where(['pr_Branch_Level1_ApprovedBy' => null, 'pr_Branch_Level1_CancelledBy' => null])
      ->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where('invgroup_id', 2);

      $this->model->with(['purchaseRequestDetails'=>function ($q){
        $q->with('itemMaster')->where('pr_DepartmentHead_ApprovedBy', '!=', null);
      }]);

    }
  }

  private function forDepartmentHead(){
    $this->model->where('pr_DepartmentHead_ApprovedBy', '!=', null)->where('warehouse_Id', $this->authUser->warehouse_id);
  }

  private function forConsultant(){
    $this->model->where('pr_Branch_Level1_ApprovedBy', '!=', null)->where('invgroup_id', 2);
  }

  private function forAdministrator(){
    $this->model->where('pr_Branch_Level1_ApprovedBy', '!=', null)->where('invgroup_id', '!=', 2);
  }

  private function forCanvas(){
    $this->model->where('pr_Branch_Level1_ApprovedBy', '!=', null);
  }

}