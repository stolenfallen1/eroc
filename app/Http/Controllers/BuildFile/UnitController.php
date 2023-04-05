<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Currency;
use App\Models\BuildFile\Unitofmeasurement;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(){
        return response()->json(['units' => Unitofmeasurement::all()], 200);
    }
    public function getCurrencies(){
        return response()->json(['currencies' => Currency::all()], 200);
    }
}