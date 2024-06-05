<?php

namespace App\Helpers\SearchFilter\inventory;

use App\Models\MMIS\inventory\StockTransferMaster;
use Illuminate\Support\Facades\Auth;

class StockTransfer {
  protected $model;
  protected $authUser;

  public function __construct()
  {
    $this->model = StockTransferMaster::query();
    $this->authUser = auth()->user();
  }

  public function searchable(){

    $this->model->with('stockTransferDetails','warehouseSender', 'warehouseReceiver', 'tranferBy', 'receivedBy','status');
    $this->byTab();
    $this->byWarehouse();
    $this->model->orderBy('id','desc');
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  private function byWarehouse(){
    $auth_user = Auth::user();
    $this->model->where(function($q) use($auth_user){
      $q->where('sender_warehouse_id', $auth_user->warehouse_id)
      ->orWhere('receiver_warehouse_id', $auth_user->warehouse_id);
    });
  }

  private function byTab(){
    if(Request()->tab == 1){
      $this->model->whereIn('status',['12','5']);
    }elseif (Request()->tab == 2) {
      $this->model->whereIn('status', ['13','5']);
    }
  }
  
}