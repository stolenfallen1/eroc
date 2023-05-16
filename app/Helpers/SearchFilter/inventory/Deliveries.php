<?php

namespace App\Helpers\SearchFilter\inventory;

use App\Models\MMIS\inventory\Delivery;
use Carbon\Carbon;

class Deliveries
{
  protected $model;
  protected $authUser;
  public function __construct()
  {
    $this->model = Delivery::query();
    $this->authUser = auth()->user();
  }

  public function searchable(){
    $this->model->with('status', 'vendor', 'warehouse');
    $this->byTab();
    $this->searcColumns();
    if($this->authUser->role->name == 'dietary' || $this->authUser->role->name == 'dietary head'){
      $this->model->whereHas('purchaseOrder', function($q){
        $q->whereHas('purchaseRequest', function($q1){
          $q1->where('isPersihable', 1);
        });
      });
    }else{
      $this->model->whereHas('purchaseOrder', function($q){
        $q->whereHas('purchaseRequest', function($q1){
          $q1->where(function($q2){
            $q2->where('isPersihable', 0)->orWhere('isPersihable', NULL);
          });
        });
      });
    }
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function searcColumns(){
    $searchable = ['rr_Document_Invoice_No', 'rr_number', 'po_number'];
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where(function ($q) use ($keyword, $searchable) {
        foreach ($searchable as $column) {
          if($column == 'rr_number'){
            $q->orWhereRaw("CONCAT(rr_Document_Prefix,'',rr_Document_Number,'',rr_Document_Suffix) = ?", $keyword );
          }else if($column == 'po_number'){
            $q->orWhereRaw("CONCAT(po_Document_Prefix,'',po_Document_Number,'',po_Document_Suffix) = ?", $keyword );
          }
          
          else{
            $q->orWhere($column, 'LIKE', "%" . $keyword . "%");
          }

        }
      });
    }
  }

  public function byTab()
  {
    if(Request()->tab == 1){
      $this->model->where('rr_Status', 5);
    }else if( Request()->tab == 2){
      $this->model->where('rr_Status', 11);
    }
  }

}