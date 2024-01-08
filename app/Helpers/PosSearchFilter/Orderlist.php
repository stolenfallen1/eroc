<?php

namespace App\Helpers\PosSearchFilter;

use Carbon\Carbon;
use App\Models\POS\Orders;
use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Warehouseitems;
use App\Helpers\PosSearchFilter\Terminal;

class Orderlist
{
    protected $model;
    public function __construct()
    {
        $this->model = Orders::query();
    }

    public function searchable()
    {
        $this->model->with('customers','order_items','order_items.ItemBatch','payment','order_items.vwItem_details')->orderBy('id', 'desc');
        $this->searchOrderStatus();
        $this->searchTerminal();
        $this->searchColumns();
        $this->dateTransaction();
        $per_page = Request()->per_page ?? '1';
        return $this->model->paginate($per_page);
    }

    public function sales_order_searchable()
    {
        $this->model->with('customers','order_items','order_items.ItemBatch','payment')->orderBy('id', 'desc');
        $this->searchTerminal();
        $this->haspaymentonly();
        $this->salesorder_searchColumns();
        $per_page = Request()->per_page ?? '1';
        return $this->model->where('order_status_id',9)->paginate($per_page);
    }
    public function salesorder_searchColumns()
    {
        $this->model->where('pick_list_number', 'LIKE', "" . Request()->keyword . "%")->orWhereHas('payment', function($payment){
            return $payment->where('sales_invoice_number', 'LIKE', "" . Request()->keyword . "%");
        });
    }
    public function returnordersearchable()
    {
        $this->model->with('customers','order_items','order_items.ItemBatch','payment','order_items.vwItem_details')->orderBy('id', 'desc');
        $this->searchColumns();
        $this->searchTerminal();
        $this->dateTransaction();
        $this->haspaymentonly();
        $per_page = Request()->page ?? '1';
        return $this->model->paginate(12);
    }

    public function haspaymentonly(){
        $this->model->has('payment');
    }

    public function searchColumns()
    {
        $digit = 10;
        $lenght = strlen(Request()->keyword);
        $zero = $digit - $lenght;
        $ordernumber = str_pad(0, $zero, "0", STR_PAD_LEFT).''.(int)Request()->keyword;
        
        if(Request()->type){
            $this->model->where('order_status_id',Request()->type);
        }
        if(Request()->keyword){
            $this->model->where('pick_list_number', 'LIKE', '%'.$ordernumber.'%');
        }
    }
    public function searchOrderStatus()
    {
        if(Request()->type){
            $this->model->where('order_status_id',Request()->type);
        }
    }
    
    public function dateTransaction()
    {
        if(isset(Request()->payload['datetransaction'])){
            $this->model->whereDate('order_date',Request()->payload['datetransaction']);
        }else{
            $this->model->whereDate('order_date',Carbon::now()->format('Y-m-d'));
        }
    }

    public function searchTerminal(){
        $user_terminal = (new Terminal)->terminal_details();
       
        if(Auth::user()->role->name == 'POS Take Order'){
            $this->model->where('terminal_id',$user_terminal->id);
        }else if(Auth::user()->role->name == 'POS Cashier'){ 
            $this->model->where('terminal_id',Auth()->user()->terminal_id);
            // $this->model->where('take_order_terminal_id',Auth()->user()->terminal_id);
        }
    }
    
}
