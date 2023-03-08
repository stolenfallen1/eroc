<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Genericnames;
use Illuminate\Http\Request;

class GenericNameController extends Controller
{
    public function index(){
        return response()->json(['generic_name' => Genericnames::all()], 200);
    }
}
