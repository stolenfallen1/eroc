<?php

namespace App\Http\Controllers\HIS\services\emergency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\services\Patient;
use \Carbon\Carbon;

class EmergencyPatientList extends Controller
{
    //
    public function registeredPatientList() {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $data = Patient::query();
            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 5)  
                    ->where('isRevoked', 0)              
                    ->whereDate('registry_Date', $today);
            });
            if (Request()->has('keyword')) {
                $keyword = Request()->keyword;
                $data->where(function($subQuery) use ($keyword) {
                    $subQuery->where('lastname', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('firstname', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('patient_id', 'LIKE', '%' . $keyword . '%');
                });
            }
            $data->with([
                'sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries',
                'patientRegistry' => function($query) use ($today) {
                    $query->whereDate('registry_Date', $today)
                        ->where('mscAccount_Trans_Types', 5)
                        ->where('isRevoked', 0)
                        ->with(['allergies' => function($allergyQuery) use ($today) {
                            $allergyQuery->with('cause_of_allergy', 'symptoms_allergy', 'drug_used_for_allergy')
                                            ->where('isDeleted', '!=', 1)
                                            ->whereDate('created_at', $today);
                        }]);
                }
            ]);
            $data->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function revokedPatientList(Request $request) {
        try {
            $data = Patient::query();
            $data->with('sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries', 'patientRegistry');
            $today = Carbon::now()->format('Y-m-d');
            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_trans_types', 5);
                $query->where('isRevoked', 1);
                if(Request()->keyword) {
                    $query->where(function($subQuery) {
                        $subQuery->where('lastname', 'LIKE', '%'.Request()->keyword.'%')
                            ->orWhere('firstname', 'LIKE', '%'.Request()->keyword.'%')
                            ->orWhere('patient_id', 'LIKE', '%'.Request()->keyword.'%');
                    });
                }
            });
            $data->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch(\Exception $e) {
            return response()->json([
                'message' => 'Failed to get revoked Emergency data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
