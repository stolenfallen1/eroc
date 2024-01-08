<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Systerminals;
use App\Models\BuildFile\vwTerminalTakeOrder;

class Terminal
{
    public function check_terminal($terminalid){
        if(Auth::user()->role->name == 'Pharmacist' || Auth::user()->role->name == 'admin') {
            $count = Systerminals::where('terminal_ip_address',$terminalid)->count();
            return $count;
        }else if(Auth::user()->role->name == 'POS Take Order'){
            $count =  vwTerminalTakeOrder::where('terminal_ip_address',$terminalid)->count();
            return $count;
        }else if(Auth::user()->role->name == 'POS Cashier'){ 
            $count =  Systerminals::where('terminal_ip_address',$terminalid)->count();
            return $count;
        }
    }

    public function terminal_details()
    {
        if(Auth::user()->role->name == 'Pharmacist' || Auth::user()->role->name == 'admin') {
            return Systerminals::where('terminal_ip_address',Auth()->user()->user_ipaddress)
            ->select('terminal_code','terminal_name','id as terminal_Id','id','terminal_ip_address','terminal_Machine_Identification_Number','terminal_serial_number','isitem_Selling_Price_Out','isitem_Selling_Price_In')
            ->first();
        }else if(Auth::user()->role->name == 'POS Take Order'){
            return vwTerminalTakeOrder::where('terminal_ip_address',Auth()->user()->user_ipaddress)
            ->select('terminal_code','terminal_Id','terminal_name','id','terminal_ip_address','terminal_Machine_Identification_Number','terminal_serial_number','isitem_Selling_Price_Out','isitem_Selling_Price_In')
            ->first();
        }else if(Auth::user()->role->name == 'POS Cashier'){ 
            return Systerminals::where('terminal_ip_address',Auth()->user()->user_ipaddress)
            ->select('terminal_code','id as terminal_Id','id','terminal_name','terminal_ip_address','terminal_Machine_Identification_Number','terminal_serial_number','isitem_Selling_Price_Out','isitem_Selling_Price_In')
            ->first();
        }
    }

    public function TakeOrderTerminal(){

        $list = vwTerminalTakeOrder::where('terminal_id',Auth()->user()->terminal_id)->select('terminal_code','id as terminal_Id','id','terminal_name','terminal_ip_address','isitem_Selling_Price_Out','isitem_Selling_Price_In')->get();
        $terminalid =[];
        if($list){
            foreach($list as $row){
                $terminalid[] = $row['id'];
            }  
            return $terminalid;
        }
    }
}
