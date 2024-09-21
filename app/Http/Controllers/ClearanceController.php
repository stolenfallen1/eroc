<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use Illuminate\Http\Request;

class ClearanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    { 
        $payload = $request->all();
        $idnum = $payload['IdNum'];
        $data = Clearance::select('HospNum','LastName','FirstName','MiddleName','BirthDate')
                           ->where('LastName', 'like', '%' . $payload['lastname'] . '%')
                           ->whereHas('patient_details',function($query) use ($idnum){
                                return $query->where('IdNum',$idnum);
                           })->first(); 
                           
        return response()->json($data,200);
    }

}
