<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Support\Facades\Auth;

class AuthController extends \TCG\Voyager\Http\Controllers\Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
       
        if ($this->guard()->attempt($credentials, $request->has('remember'))) {
          
            $user = Auth::user();
            $token = $user->createToken();
            $user->load('role.permissions', 'roles');
            
            return response()->json(['user' => $user, 'access_token' => $token], 200);
            // return $this->sendLoginResponse($request);
        }
        return response()->json(["message" => 'Invalid login'], 403);

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
        $data['module'] = $modulelist;
        $data['details'] = Auth::user();
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
