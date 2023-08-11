<?php

namespace App\Helpers\PosSearchFilter;

use App\Models\POS\vwWarehouseItems;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;

class Items
{
    protected $model;
    public function __construct()
    {
        $this->model = vwWarehouseItems::query();
    }

    public function searchable()
    {
        // $this->model->with('itemMaster', 'unit', 'itemMaster.brand');
        $this->byCategory();
        $this->searchColumns();
        // $this->warehouse();
        $this->branch();
        // $this->items();
        $this->model->where('isactive', '1');
        $this->model->orderby('item_name', 'asc');
        return $this->model->paginate(20);
    }

    public function searchColumns()
    {
        
        if (isset(Request()->payload['keyword'])) {
            $this->model->where('item_name', 'LIKE', Request()->payload['keyword'].'%');
        }
    }

    public function byCategory()
    {
        $category_id = Request()->payload['category'] ?? '';
        if ($category_id != 0) {
            $this->model->where('item_Category_Id',  Request()->payload['category']);
        }
    }

    public function warehouse()
    {
        $departmentid = Request()->departmentid ?? '';
        if ($departmentid) {
            $this->model->where('warehouse_Id',Request()->departmentid);
        }else{
            $this->model->where('warehouse_Id',Auth()->user()->departmentid);
        }
    }

    public function branch()
    {
        $branchid = Request()->branchid ?? '';
        if ($branchid) {
            $this->model->where('branch_id',Request()->branchid);
        }else{
            $this->model->where('branch_id',Auth()->user()->branch_id);
        }
    }
    private function items(){
        $items =  Request()->payload['items'] ?? '';
        if($items){
            $itemid = [];
            foreach($items as $item){
                $itemid[] = $item['id'];
            }
            if ($items) {
                $this->model->whereNotIn('item_Id',json_decode(json_encode($itemid)));
            }
        }
    }
    
}
