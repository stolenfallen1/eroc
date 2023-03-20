<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getDepartmentUsers()
    {
      return response()->json(['users' => User::where('warehouse_id', Auth::user()->warehouse_id)->get()]);
    }
}
