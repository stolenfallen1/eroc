<?php

namespace App\Http\Controllers\HIS\services\in_patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use App\Models\HIS\AdmittingCommunicationFile;
use \Carbon\Carbon;

class InPatientList extends Controller {
    //
    public function registeredPatientList() {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $data = Patient::query();
            $data->whereHas('patientRegistry', function ($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 6)
                    ->where('isRevoked', 0)
                    ->where(function ($q) use ($today) {
                        $q->whereDate('registry_Date', $today)
                            ->orWhereNull('discharged_Date');
                    });
            });
            if (request()->has('keyword')) {
                $keyword = request()->keyword;
                $data->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('lastname', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('firstname', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('patient_id', 'LIKE', '%' . $keyword . '%');
                });
            }
            $data->with([
                'sex',
                'civilStatus',
                'region',
                'provinces',
                'municipality',
                'barangay',
                'countries',
                'patientRegistry' => function ($query) use ($today) {
                    $query->where('mscAccount_Trans_Types', 6);
                    $query->where('isRevoked', 0);
                    $query->where(function ($q) use ($today) {
                        $q->whereDate('registry_Date', $today)
                            ->orWhereNull('discharged_Date');
                    })
                        ->with([
                            'allergies' => function ($allergyQuery) use ($today) {
                                $allergyQuery->with('cause_of_allergy', 'symptoms_allergy', 'drug_used_for_allergy')
                                    ->where('isDeleted', '!=', 1)
                                    ->whereDate('created_at', $today);
                            }
                        ]);
                },
            ]);
            $data->orderBy('id', 'desc');
            $page = request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get patients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPatientForAdmission() {
        try {
            $data = Patient::query();
            $data = AdmittingCommunicationFile::with('patientMaster', 'patientRegistry')
                    ->select('patient_Id', 'case_No')
                    ->whereNull('admittedDate')
                    ->get();
            if(!empty($data)) {
                return response()->json($data, 200);
            } else {
                return response()->json([
                    'message' => 'No data found',
                    'error' => 'No data found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSelectedPatientForAdmission($id) {
        try {
            $fetchPatientData = PatientRegistry::where('case_No', $id)->first();
            if($fetchPatientData) {
                $data = Patient::query();
                $data->whereHas('patientRegistry', function ($query) use ($fetchPatientData) {
                    $query->where('mscAccount_Trans_Types', 5)
                        ->where('isRevoked', 0)
                        ->where('patient_Id', $fetchPatientData->patient_Id); 
                });
                $data->with([
                    'sex',
                    'civilStatus',
                    'region',
                    'provinces',
                    'municipality',
                    'barangay',
                    'countries',
                    'patientRegistry' => function ($query) use ($id, $fetchPatientData) {
                        $query->where('mscAccount_Trans_Types', 5)
                            ->where('patient_Id', $fetchPatientData->patient_Id) 
                            ->where('case_No', $id)
                            ->with([
                                'allergies' => function ($allergyQuery) {
                                    $allergyQuery->with('cause_of_allergy', 'symptoms_allergy', 'drug_used_for_allergy')
                                        ->where('isDeleted', '!=', 1);
                                }
                            ]);
                    },
                ]);
                $data->orderBy('id', 'desc');
                $page = request()->per_page ?? '50';
                return response()->json($data->paginate($page), 200);
            } else {
                return response()->json([
                    'message' => 'No data found',
                    'error' => 'No data found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get patients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function revokedPatientList(Request $request) {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $data = Patient::query();
            $data->with('sex', 'civilStatus', 'region', 'provinces', 'municipality', 'barangay', 'countries', 'patientRegistry');
            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_trans_types', 6);
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
