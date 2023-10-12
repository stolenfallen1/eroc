<?php

namespace App\Http\Controllers\Schedules;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class SchedulingDashboard extends Controller
{

    public function getSchedulingDashboard()
    {
        $data['opthadata'] = $this->getOPTHA();
        $data['ordata'] = $this->getOR();
        return response()->json($data);
    }

     public function getOPTHA()
    {
        $filename = 'scheduling/OPTHA-' . date('Y-m-d') . '.json';
        if (Storage::disk('public')->exists($filename)) {
            $existingData = Storage::disk('public')->get($filename);
            return json_decode($existingData, JSON_PRETTY_PRINT);
        }
       
    }

     public function getOR()
    {
        
        $filename = 'scheduling/OR-' . date('Y-m-d') . '.json';
        if (Storage::disk('public')->exists($filename)) {
           
            $existingData = Storage::disk('public')->get($filename);
            return json_decode($existingData, JSON_PRETTY_PRINT);

        }
       
    }
}
