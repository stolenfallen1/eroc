<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Priority;
use Illuminate\Http\Request;

class PriorityController extends Controller
{
    public function index(){
        return response()->json(["priorities" => Priority::get()]);
    }
}
