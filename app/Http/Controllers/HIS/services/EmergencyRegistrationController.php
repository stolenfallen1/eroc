<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmergencyRegistrationController extends Controller
{
    //
    public function register() {
        return response()->json([
            'message' => 'Emergency registration successful'
        ]);
    }
}
