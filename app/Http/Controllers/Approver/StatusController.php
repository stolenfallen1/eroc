<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\Approver\invStatus;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index(){
        return response()->json(["status" => invStatus::get()]);
    }
}
