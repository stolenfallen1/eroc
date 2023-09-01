<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\GetIP;
use Illuminate\Http\Request;
use App\Models\POS\POSSettings;
use TCG\Voyager\Facades\Voyager;
use App\Models\POS\POSBIRSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Helpers\PosSearchFilter\Terminal;

class AuthPOSController extends \TCG\Voyager\Http\Controllers\Controller
{
    protected $shift;
   
    public function login(Request $request)
    {
        $credentials = $request->only('idnumber', 'password');
     

        if ($this->guard()->attempt($credentials, $request->has('remember'))) {
            $ipaddress = (new GetIP)->value();
            if(!$this->checkTerminal()){
                return response()->json(["message" => 'Your not allowed to access'], 200);
            }
            if((new Terminal)->check_terminal($ipaddress) == 0){
                return response()->json(["message" => 'Your not allowed to access'], 200);
            }
            $shift = Auth()->user()->shift;
            if(Request()->shift != 0) {
                $shift = Request()->shift;
            }
            User::where('id', Auth::user()->id)->update(
                [
                    'user_ipaddress'=>(new GetIP())->value(),
                    'terminal_id'=>(new Terminal())->terminal_details()->id,
                    'shift'=> $shift,
                ]
            );
            $user = Auth::user();
            $token = $user->createToken();
            $user->load('role.permissions', 'roles');
            return response()->json(
                [
                    'user' => $user, 
                    'access_token'=>$token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration'),
                ],
             200);
            // return $this->sendLoginResponse($request);
        }
        return response()->json(["message" => 'Warning: Enter valid idnumber and password before proceeding!'], 200);
    }

    public function refreshToken(Request $request)
    {
        $accessToken = auth()->user()->token();
        
        // Revoke the current access token
        $accessToken->revoke();
        
        // Generate a new access token and return it
        $newAccessToken = $request->user()->createToken();

        return response()->json(['access_token' => $newAccessToken]);
    }
    public function checkTerminal(){
        $termninal = '';
        $hostname = gethostname();
        $ipaddress = (new GetIP)->value();
        $checksystemtermnial = (new Terminal)->terminal_details();
        if(!$checksystemtermnial){
            return false;
        }
       
        $termninal = $checksystemtermnial;
        return $termninal;
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

        $user = Auth::user();
        

        $user->sysTerminal = $this->checkTerminal();
        $user->pos_setting = POSSettings::where('isActive','1')->first();
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
