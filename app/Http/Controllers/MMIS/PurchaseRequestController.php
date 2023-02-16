<?php

namespace App\Http\Controllers\MMIS;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestController extends Controller
{
    public function index(){
       echo Auth::User();
    }

    public function store(Request $request){
        
    }

    public function update(Request $request, $id){

    }

    public function destroy($id){

    }
}
