<?php

namespace App\Http\Controllers\MMIS;

use App\Helpers\Tools\AuditTrails;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(){
        return (new AuditTrails)->searchable();
    }
}
