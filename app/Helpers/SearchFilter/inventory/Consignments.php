<?php

namespace App\Helpers\SearchFilter\inventory;

use Carbon\Carbon;
use App\Models\MMIS\Audit;
use App\Helpers\ParentRole;
use App\Models\MMIS\inventory\Consignment;

class Consignments
{
  protected $model;
  protected $authUser;
  protected $role;
  public function __construct()
  {
    $this->model = Consignment::query();
    $this->authUser = auth()->user();
    $this->role = new ParentRole();
  }

  public function searchable(){
    $this->model->with('status', 'vendor', 'warehouse','items','items.item','items.item.wareHouseItem','items.batchs');
    $this->byTab();
    $this->byWarehouse();
    $this->searcColumns();
    $this->model->whereNull('isactive');
    $this->model->orderBy('id','desc');
    $per_page = Request()->per_page;
    if ($per_page=='-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function byWarehouse(){
    if($this->role->audit()){
      $this->model->with(['warehouse', 'items', 'receiver', 'purchaseOrder' => function($q1){
        $q1->with(['deliveryItems' => function($q2){
          $q2->with('delivery.audit.user', 'item', 'unit')->whereHas('delivery', function($q3){
            // $q3->whereHas('audit');
          });
        },'purchaseRequest' => function($q5){
          $q5->with(['itemGroup', 'user', 'category','purchaseRequestDetails'=>function($qq){
            $qq->with('itemMaster','unit');
          }]);
        }, 'comptroller', 'administrator', 'corporateAdmin', 'president', 'details' => function($q2){
          $q2->with(['purchaseRequestDetail' => function($q3){
            $q3->with(['purchaseRequest' => function($q4){
              $q4->with('warehouse', 'itemGroup', 'user', 'category');
            }, 'itemMaster', 'unit', 'unit2', 'depApprovedBy', 'adminApprovedBy', 'conApprovedBy', 'recommendedCanvas']);
          }, 'canvas.vendor']);
        }]);
      }]);
      if(Request()->isauditted){
        $this->model->with('audit')->where(function($q){
          $q->where('isaudit', 1);
        });
        $this->model->join('audits', 'audits.delivery_id', '=', 'RRMaster.id')->select('audits.*', 'RRMaster.*')
        ->orderBy('audits.created_at', 'DESC');
      }else{
        $this->model->where(function($q){
          $q->where('isaudit', 0)->orWhere('isaudit', NULL);
        });
      }
    }else{
      $this->model->where('rr_Document_Warehouse_Id', $this->authUser->warehouse_id)->where('rr_Document_Branch_Id', $this->authUser->branch_id);
    }
  }

  public function searcColumns(){
    $searchable = ['rr_Document_Invoice_No', 'rr_number', 'po_number'];
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where(function ($q) use ($keyword, $searchable) {
        foreach ($searchable as $column) {
          if($column == 'rr_number'){
            $q->orWhereRaw("CONCAT(rr_Document_Prefix,'',rr_Document_Number,'',rr_Document_Suffix) = ?", $keyword )
            ->orWhere('rr_Document_Number', 'LIKE' , '%' . $keyword);
          }else if($column == 'po_number'){
            $q->orWhereRaw("CONCAT(po_Document_Prefix,'',po_Document_Number,'',po_Document_Suffix) = ?", $keyword )
            ->orWhere('po_Document_number', 'LIKE' , '%' . $keyword);
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
    if(Request()->tab == 3){
      $this->model->where('isConsignment', 1);
      $this->model->whereNull('receivedstatus');
    }else if( Request()->tab == 4){
      $this->model->where('isConsignment', 1);
    }
  }

}