<?php

namespace App\Http\Controllers\HIS\services\out_patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\services\Patient;
use \Carbon\Carbon;

class OutPatientList extends Controller {
    //
    public function registeredPatientList() {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $data = Patient::query();
            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 2)  
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
                        ->where('mscAccount_Trans_Types', 2)
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
            $today = Carbon::now()->format('Y-m-d');
            $keyword = Request()->keyword;
            $data = Patient::query();
            if(empty($keyword)) {
                $data->whereHas('patientRegistry', function($query) use ($today) {
                    $query->where('mscAccount_Trans_Types', 2)  
                        ->where('isRevoked', 1)
                        ->whereDate('revoked_Date', $today);             
                });
            } else {
                $data->whereHas('patientRegistry', function($query) {
                    $query->where('mscAccount_Trans_Types', 2)  
                        ->where('isRevoked', 1);  
                    $query->where(function($q){
                        $q->whereNotNull('revoked_Date')
                            ->orWhereNotNull('revoked_Remarks');
                    });
                });
            }
            if (Request()->has('keyword')) {
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
                        ->where('mscAccount_Trans_Types', 2)
                        ->where('isRevoked', 1)
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
}
