<?php

namespace App\Http\Controllers\BuildFile;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\Drugadministration;
use Illuminate\Http\Request;

class DrugAdministrationController extends Controller
{
    public function index(){
        return response()->json(['drug_administration' => Drugadministration::all()], 200);
    }
}
