<?php

namespace App\Http\Controllers\POS;

use Illuminate\Http\Request;
use App\Models\POS\CustomerGroup;
use App\Http\Controllers\Controller;

class CustomerGroupsController extends Controller
{
    public function index()
    {
        $data =  CustomerGroup::all();;
        return response()->json(["data"=>$data,"message" => "success"], 200);
    }
}
