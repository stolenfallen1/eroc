<?php

namespace App\Helpers\SearchFilter;

use App\Models\BuildFile\Itemcategories;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Itemmasters;
use App\Models\BuildFile\Warehouseitems;
use App\Models\BuildFile\Warehouses;

class Items
{
  protected $model;
  public function __construct()
  {
    $this->model = Itemmasters::query();
  }

  public function searchable()
  {
    $this->model->with('itemGroup', 'itemCategory', 'unit','wareHouseItem');
    $this->byBranch();
    $this->byCategory();
    $this->bySubCategory();
    $this->byInventoryGroup();
    $this->byTab();
    $this->searchColumns();
    $this->withWareHouseItems();
    $this->withWareHouseItem();
    $this->byWarehouse();
    $this->forLocation();
    $this->forStockRequisition();
    $this->forConsignment();
    $per_page = Request()->per_page;
    if ($per_page == '-1') return $this->model->paginate($this->model->count());
    return $this->model->paginate($per_page);
  }

  public function searchColumns()
  {
    $searchable = ['item_name', 'item_Description', 'item_SKU', 'item_Barcode'];
    if (Request()->keyword) {
      $keyword = Request()->keyword;
      $this->model->where(function ($q) use ($keyword, $searchable) {
        foreach ($searchable as $column) {
          $q->orWhere($column, 'LIKE', "%" . $keyword . "%");
        }
      });
    }
  }

  private function forLocation(){
    if(Request()->for_location){
      $this->model->with('wareHouseItems')->whereDoesntHave('wareHouseItems', function ($query) {
        $query->where('warehouse_Id', Auth::user()->warehouse_id);
      });
    }
  }

  private function withWareHouseItems(){
    if(Request()->wareHouseItems){
      $this->model->with('wareHouseItems','wareHouseItems.branch');
    }
  }

  private function withWareHouseItem(){
    if(Request()->wareHouseItem || Request()->for_sr){
      $this->model->with('wareHouseItem','wareHouseItem.branch','wareHouseItem.warehouse');
    }
  }

  private function forStockRequisition(){
    if(Request()->for_sr){
      $ids = Warehouseitems::where('branch_id', Auth::user()->branch_id)->where('warehouse_Id', Auth::user()->warehouse_id)->get()->pluck('item_Id');
      $this->model->whereIn('id', $ids);
      // ->whereHas('wareHouseItem', function($q){
      //   $q->where('item_OnHand', '>', 0);
      // });
      // $this->model->with('wareHouseItem');
    }
  }

  private function forConsignment(){
    if(Request()->consignment){
      $this->model->whereHas('wareHouseItems', function($q1){
        $q1->where('isConsignment', 1)->where('warehouse_Id', Auth::user()->warehouse_id)->where('branch_id', Auth::user()->branch_id);
      });
    }
  }

  private function byTab()
  {
    if(Request()->tab=="0"){
      $warehouse = Warehouses::with('itemGroups.categories')->findOrFail(Auth::user()->warehouse_id);
      // return $category = $warehouse->itemGroups[0]->categories->pluck('id');
      if(sizeof($warehouse->itemGroups)){
        $this->model->where('item_InventoryGroup_Id', $warehouse->itemGroups[0]->id);
        if(Request()->for_item_master){
          $category = $warehouse->itemGroups[0]->categories->pluck('id');
          $this->model->whereIn('item_Category_Id',  $category);
        }
      }else{
        $this->model->where('item_InventoryGroup_Id', 0);
      }
    }else{
      if (Request()->tab) {
        $this->model->where('item_InventoryGroup_Id', Request()->tab);
        $category = Itemcategories::where('invgroup_id', Request()->tab)->get()->pluck('id');
        $this->model->whereIn('item_Category_Id',  $category);
      }
    }
  }

  private function byWarehouse()
  {
    $warehouse = Request()->warehouse_idd;
    if ($warehouse) {
      $this->model->with('wareHouseItems')->whereHas('wareHouseItems', function ($query) use ($warehouse) {
        // $query->where('warehouse_Id', $warehouse)->where('branch_id', Auth::user()->branch_id); 
        $query->where('warehouse_Id', $warehouse)->where('branch_id', Auth::user()->branch_id); 
      });
    }
  }

  private function byBranch()
  {
    $branch = Request()->branch_id;
    if ($branch) {
      $this->model->whereHas('wareHouseItems', function ($q) use ($branch) {
        $q->whereHas('warehouse', function($q1) use ($branch) {
          $q1->where('warehouse_Branch_Id', $branch);
        });
      });
      $this->model->with('wareHouseItem');
    }
  }

  private function byCategory()
  {
    $category_id = Request()->category_id;
    if ($category_id) {
      $this->model->where('item_Category_Id', $category_id);
    }
  }

  private function bySubCategory()
  {
    $subcategory_id = Request()->subcategory_id;
    if ($subcategory_id) {
      $this->model->where('item_SubCategory_Id', $subcategory_id);
    }
  }

  private function byInventoryGroup()
  {
    $item_InventoryGroup_Id = Request()->item_InventoryGroup_Id;
    if ($item_InventoryGroup_Id) {
      $this->model->where('item_InventoryGroup_Id', $item_InventoryGroup_Id);
    }
  }
}
