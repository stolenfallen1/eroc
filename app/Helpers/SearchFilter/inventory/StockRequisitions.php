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

    // $this->byTab();
    $this->byWarehouse();

    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  private function byWarehouse()
  {
    $auth_user = Auth::user();
    $this->model->where(function($q) use($auth_user){
      $q->where('requester_warehouse_id', $auth_user->warehouse_id)->orWhere('sender_warehouse_id', $auth_user->warehouse_id);
    })->where(function($q) use($auth_user){
      $q->where('requester_branch_id', $auth_user->branch_id)->orWhere('sender_branch_id', $auth_user->branch_id);
    });
  }

  private function byTab()
  {
    if (Request()->tab == 1) {
      $this->model->where('status', 1002);
    } elseif (Request()->tab == 2) {
      $this->model->where('status', 1003);
    }
  }
}
