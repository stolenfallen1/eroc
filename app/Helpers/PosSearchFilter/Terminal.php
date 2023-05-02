<?php

namespace App\Helpers\PosSearchFilter;

use Illuminate\Support\Facades\Auth;
use App\Models\BuildFile\Systerminals;
use App\Models\BuildFile\vwTerminalTakeOrder;

class Terminal
{

    public function terminal_details()
    {
        if(Auth::user()->role->name == 'Pharmacist' || Auth::user()->role->name == 'admin') {
            return Systerminals::where('terminal_ip_address',Auth()->user()->user_ipaddress)->select('terminal_code','id','terminal_Machine_Identification_Number','terminal_serial_number')->first();
        }else if(Auth::user()->role->name == 'Pharmacist Assistant'){
            return vwTerminalTakeOrder::where('terminal_ip_address',Auth()->user()->user_ipaddress)->select('terminal_code','id','terminal_Machine_Identification_Number','terminal_serial_number')->first();
        }else if(Auth::user()->role->name == 'Pharmacist Cashier'){ 
            return Systerminals::where('terminal_ip_address',Auth()->user()->user_ipaddress)->select('terminal_code','id','terminal_Machine_Identification_Number','terminal_serial_number')->first();
        }
    }
    public function TakeOrderTerminal(){

        $list = vwTerminalTakeOrder::where('terminal_id',Auth()->user()->terminal_id)->select('terminal_code','id','terminal_Machine_Identification_Number','terminal_serial_number')->get();
        $terminalid =[];
        if($list){
            foreach($list as $row){
                $terminalid[] = $row['id'];
            }  
            return $terminalid;
        }
        
    }
}
