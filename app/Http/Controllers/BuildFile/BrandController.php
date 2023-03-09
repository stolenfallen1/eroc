<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Brands;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(){
        return response()->json(['brand' => Brands::all()], 200);
    }
}