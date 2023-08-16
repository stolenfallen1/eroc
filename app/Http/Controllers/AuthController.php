<?php

namespace App\Http\Controllers;

use App\Helpers\GetIP;
use App\Helpers\PosSearchFilter\Terminal;
use App\Models\BuildFile\Systerminals;
use App\Models\User;
use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Support\Facades\Auth;

class AuthController extends \TCG\Voyager\Http\Controllers\Controller
{
    protected $shift;
   
    public function login(Request $request)
    {
        $credentials = $request->only('idnumber', 'password');


        
        if ($this->guard()->attempt(['idnumber' => $credentials['idnumber'], 'password' => $credentials['password']], $request->has('remember'))) {
            $user = Auth::user();
            $token = $user->createToken();
            $user->load('role.permissions', 'roles');
            return response()->json(['user' => $user, 'access_token' => $token], 200);
            // return $this->sendLoginResponse($request);
        }
        return response()->json(["message" => 'Warning: Enter valid email and password before proceeding!'], 200);
    }
    
    public function checkTerminal(){
        $termninal = '';
        $hostname = gethostname();
        $ipaddress = (new GetIP)->value();
        if(Auth::user()->role->name == 'Pharmacist' || Auth::user()->role->name == 'Pharmacist Assistant' || Auth::user()->role->name == 'Pharmacist cashier') {
            $checksystemtermnial = (new Terminal)->terminal_details();
            if(!$checksystemtermnial){
                return false;
            }
            $termninal = $checksystemtermnial;
            return $termninal;
        }else{
            return true;
        }
    }

    public function logout()
    {
        Auth::user()->revokeToken();
        Auth::guard('web')->logout();

        return response()->json(['message' => 'success'], 200);
    }


    public function userDetails(){
        // get user details

        // get all user module 
        $modulelist = Voyager::model('MenuItem')->whereNull('parent_id')->where('menu_id','1')->orderBy('order','asc')->get();
        $modulelist->filter(function ($item) {
            // check if action 
            return !$item->children->isEmpty() || Auth::user()->can('browse', $item);
      
        })->filter(function ($item) {
            // Filter out empty menu-items
            if ($item->url == '' && $item->route == '' && $item->children->count() == 0) {
                return false;
            }
            return true;
        });

        if(!$this->checkTerminal()){
            return response()->json(["message" => 'Your not allowed to access'], 403);
        }

        $user = Auth::user();
        $user->sysTerminal = $this->checkTerminal();
        $user->serverIP =  config('app.pos_server_ip');
        $data['module'] = $modulelist;
        $data['details'] = $user;
        $data['submodule'] = $this->systemsubcomponents();
        return response()->json(['user' => $data], 200);
    }

    public function systemsubcomponents(){
      
        // default value for admin 
        $menuid = '1';
        // request module id module id 
        $module_id = '124';

        // submodule list base module id 
        $items = Voyager::model('MenuItem')->with('childrensub')->where('menu_id',$menuid)->where('parent_id',$module_id)->get();
        
        $data['submodule'] = $items->filter(function ($item) {
            // check if action 
            return !$item->children->isEmpty() || Auth::user()->can('browse', $item);
      
        })->filter(function ($item) {
            // Filter out empty menu-items
            if ($item->url == '' && $item->route == '' && $item->children->count() == 0) {
                return false;
            }
            return true;
        });

        return $data;
    }

    public function verifyPasscode(Request $request){
        if(Auth::user()->passcode === $request->code){
            return response()->json(["message" => 'success'], 200);
        }
        return response()->json(["message" => 'Incorrect passcode'], 403);
    }
    protected function guard()
    {
        return Auth::guard('web');
    }
}
