<?php

namespace App\Helpers\SearchFilter\inventory;

use App\Models\MMIS\inventory\StockTransfer;
use Illuminate\Support\Facades\Auth;

class StockTransfers {
  protected $model;
  protected $authUser;

  public function __construct()
  {
    $this->model = StockTransfer::query();
    $this->authUser = auth()->user();
  }

  public function searchable(){

    $this->model->with('delivery', 'purchaseRequest', 'purchaseOrder', 'warehouseSender', 'warehouseReceiver', 'tranferBy', 'receivedBy');

    $this->byTab();
    $this->byWarehouse();

    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);

  }

  private function byWarehouse(){
    $auth_user = Auth::user();
    $this->model->where(function($q) use($auth_user){
      $q->where('sender_warehouse', $auth_user->warehouse_id)
      ->orWhere('receiver_warehouse', $auth_user->warehouse_id);
    });
  }

  private function byTab(){
    if(Request()->tab == 1){
      $this->model->where('status', 1002);
    }elseif (Request()->tab == 2) {
      $this->model->where('status', 1003);
    }
  }

  
}