<?php

namespace App\Http\Controllers\MMIS;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\MMIS\PurchaseRequest;

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
}
