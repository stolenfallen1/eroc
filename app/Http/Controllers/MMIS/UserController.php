<?php

namespace App\Http\Controllers\MMIS;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\MMIS\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(){
        return PurchaseRequest::with('category')->paginate(25);
    }

    public function store(Request $request){
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
    }

    public function updatePassword(Request $request){

        $user = User::findOrfail(Auth::user()->id);
        $user->makeVisible('password');
        if(!Hash::check($request->old_password, $user->password)){
            return response()->json(['error' => 'incorrect password'], 200);
        }
        if($request->passcode) $user->passcode = $request->passcode;
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['message' => 'success'], 200);
    }



    

}
