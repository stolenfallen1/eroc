<?php

namespace App\Helpers\PosSearchFilter;

use App\Models\POS\Customers;
use App\Models\POS\vwCustomers;
use Illuminate\Support\Facades\Auth;

class Customer
{
    protected $model;
    public function __construct()
    {
        $this->model = vwCustomers::query();
    }

    public function searchable()
    {
        $this->searchColumns();
        $this->model->where('isActive', '1')->orderBy('id', 'desc');
        return $this->model->paginate(5);
    }

    public function searchColumns()
    {
        if (Request()->keyword) {
            if(isset(Request()->keyword['keywordlastname'])){
                return  $this->model->where('customer_last_name', 'LIKE','%'.Request()->keyword['keywordlastname'].'%');
            }
            if(isset(Request()->keyword['keywordfirstname'])){
               return $this->model->where('customer_first_name', 'LIKE','%'.Request()->keyword['keywordfirstname'].'%');
            }
        }
       
    }

    private function byCategory()
    {
        $category_id = Request()->category_id;
        if ($category_id) {
            $this->model->where('item_Category_Id', $category_id);
        }
    }
}
