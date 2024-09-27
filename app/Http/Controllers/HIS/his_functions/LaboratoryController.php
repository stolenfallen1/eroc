<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Models\HIS\his_functions\LaboratoryExamsView;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class LaboratoryController extends Controller
{

    public function getOPDPatients()
    {
        try {
            $today = Carbon::now();
            $data = PatientRegistry::with('patient_details')
                ->whereHas('lab_services', function($query) {
                    $query->whereNotNull('case_No');
                })
                ->where('mscAccount_Trans_Types', 2) 
                // ->whereDate('created_at', $today)
                // ->whereDate('registry_Date', $today)
                ->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    public function getERPatients()
    {
        try {
            $data = PatientRegistry::with('patient_details')
                ->whereHas('lab_services', function($query) {
                    $query->whereNotNull('case_No');
                })
                ->where('mscAccount_Trans_Types', 5) 
                // ->whereNull('discharged_Date')
                ->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getIPDPatient()
    {
        try {
            $data = PatientRegistry::with('patient_details')
                ->whereHas('lab_services', function($query) {
                    $query->whereNotNull('case_No');
                })
                ->where('mscAccount_Trans_Types', 6) 
                // ->whereNull('discharged_Date')
                ->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPatientLabExams(Request $request) 
    {
        try {
            $case_No = $request->items['case_No'];
            $trans_types = $request->items['mscAccount_Trans_Types'];

            $data = LaboratoryExamsView::query()
                ->where('caseno', $case_No)
                ->where('trans_types', $trans_types)
                ->orderBy('refNum', 'desc');
            $page = Request()->per_page;
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDischargedPatientToday() 
    {
        try {
            // $today = Carbon::now();
            $data = PatientRegistry::with('patient_details')
                ->where('mscAccount_Trans_Types', 2)
                ->orderBy('id', 'asc')
                ->get();
            return response()->json([
                'message' => 'Data fetched',
                'data' => $data
            ], 200); 
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
