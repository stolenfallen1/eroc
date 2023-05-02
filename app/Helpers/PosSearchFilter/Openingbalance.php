<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\POS\OpenningAmount;

class Openingbalance
{
    protected $model;
    public function __construct()
    {
        $this->model = OpenningAmount::query();
    }

    public function searchable()
    {
        $this->model->with('cashonhand_details','user_details','user_shift')->orderBy('id', 'desc');
        $this->searchColumns();
        $per_page = Request()->per_page;
        return $this->model->paginate($per_page);
    }
   
    public function searchColumns()
    {
        if(Request()->keyword){
            $this->model->where('cashonhand_beginning_amount','LIKE',''.Request()->keyword.'%');
        }
    }
}
