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
    $this->byWarehouse();
    $this->searcColumns();
    if($this->authUser->role->name == 'dietary' || $this->authUser->role->name == 'dietary head'){
      $this->model->whereHas('purchaseOrder', function($q){
        $q->whereHas('purchaseRequest', function($q1){
          $q1->where('isPerishable', 1);
        });
      });
    }else{
      $this->model->whereHas('purchaseOrder', function($q){
        $q->whereHas('purchaseRequest', function($q1){
          $q1->where(function($q2){
            $q2->where('isPerishable', 0)->orWhere('isPerishable', NULL);
          });
        });
      });
    }
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function byWarehouse(){
    if($this->authUser->role->name == 'audit'){
      $this->model->with(['purchaseOrder'=>function($q){
        $q->with(['purchaseRequest'=> function($q1){
          $q1->with(['purchaseRequestDetails' => function($q2){
            $q2->with('itemMaster', 'unit', 'purchaseOrderDetails.purchaseOrder');
          }, 'warehouse', 'itemGroup', 'user', 'category']);
        }, 'details' => function($q1){
          $q1->with('canvas.vendor', 'item', 'unit');
        }]);
      }, 'items'=>function($q){
        $q->with('item', 'unit');
      }]);
      if(Request()->isauditted){
        $this->model->with('audit')->where(function($q){
          $q->where('isaudit', 1);
        });
      }else{
        $this->model->where(function($q){
          $q->where('isaudit', 0)->orWhere('isaudit', NULL);
        });
      }
    }else{
      $this->model->where('rr_Document_Warehouse_Id', $this->authUser->warehouse_id);
    }
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