<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Antibioticclass;
use Illuminate\Http\Request;

class AntibioticController extends Controller
{
    public function index(){
        return response()->json(['antibiotics' => Antibioticclass::all()], 200);
    }
}
