<?php

namespace App\Http\Controllers\Approver;

use App\Http\Controllers\Controller;
use App\Models\Approver\InvStatus;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index(){
        return response()->json(["status" => InvStatus::get()]);
    }
}
