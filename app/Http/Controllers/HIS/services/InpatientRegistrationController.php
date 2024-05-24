<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InpatientRegistrationController extends Controller
{
    //
    public function register() {
        return response()->json([
            'message' => 'Inpatient registration successful'
        ]);
    }
}
