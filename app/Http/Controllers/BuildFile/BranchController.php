<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Branchs;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(){
        return response()->json(['branches' => Branchs::all()]);
    }
}
