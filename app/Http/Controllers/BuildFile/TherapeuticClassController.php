<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Therapeuticclass;
use Illuminate\Http\Request;

class TherapeuticClassController extends Controller
{
    public function index(){
        return response()->json(['therapeutic_class' => Therapeuticclass::all()], 200);
    }
}
