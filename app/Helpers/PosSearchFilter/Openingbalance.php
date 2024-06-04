<?php

namespace App\Helpers\PosSearchFilter;

use Carbon\Carbon;
use App\Models\POS\OpenningAmount;
use Illuminate\Support\Facades\Auth;

class Openingbalance
{
    protected $model;
    public function __construct()
    {
        $this->model = OpenningAmount::query();
    }

    public function searchable()
    {
        $this->model->with('cashonhand_details', 'closeby_details', 'openby_details', 'user_shift')->orderBy('id', 'desc');
        $this->searchColumns();
        $this->searchShift();
        $this->byUser();
        $this->bydate();
        $this->bystatus();
        $per_page = Request()->per_page;
        return $this->model->paginate($per_page);
    }

    public function searchColumns()
    {
        if( Request()->date){
            $this->model->whereDate('report_date', Request()->date);
        }

    }
    public function searchShift()
    {
       
        if(Request()->shift){
            $this->model->where('shift_code', Request()->shift);
        }

    }
    public function bystatus()
    {
        // if(Request()->status){
            $this->model->where('isposted', Request()->status);
        // }
    }
    public function byUser()
    {
        if(Auth()->user()->role->name == 'POS Cashier') {
            $this->model->where('user_id', Auth()->user()->idnumber);
        }
    }
    public function bydate()
    {
        if(Auth()->user()->role->name == 'POS Cashier') {
            $this->model->whereDate('cashonhand_beginning_transaction', Carbon::now()->format('Y-m-d'));
        }
    }
}
