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
        $per_page = Request()->per_page ?? '1';
        return $this->model->paginate($per_page);
    }

    public function searchColumns()
    {
        $customer_name = Request()->keyword ?? '';
        $names = explode(',', $customer_name); // Split the keyword into firstname and lastname 
        $lastname = $names[0];
        $firstname = $names[1]  ?? '';

        if ($customer_name) {
            if($lastname){
                $this->model->where('customer_last_name', 'LIKE',''.$lastname.'%');
            }
            if($firstname){
                $this->model->where('customer_first_name', 'LIKE',''.$firstname.'%');
            }
            // if(isset(Request()->keyword['keywordlastname'])){
            //    return $this->model->where('customer_first_name', 'LIKE','%'.Request()->keyword['keywordfirstname'].'%');
            // }
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
