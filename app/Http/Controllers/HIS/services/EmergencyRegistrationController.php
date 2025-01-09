<?php

namespace App\Http\Controllers\HIS\services;

use App\Http\Controllers\Controller;
use App\Models\HIS\mscPatientBroughtBy;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use App\Models\HIS\mscComplaint;
use App\Models\HIS\mscServiceType;
use App\Models\HIS\PatientAllergies;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\GetIP;
use App\Helpers\HIS\SysGlobalSetting;
use App\Helpers\HIS\PatientRegistrationData;
use App\Helpers\HIS\PatientRegistrySequence;
class EmergencyRegistrationController extends Controller
{
    protected $check_is_allow_medsys;
    protected $patient_data;
    protected $sequence_number;
    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->sequence_number = new PatientRegistrySequence();
        $this->patient_data = new PatientRegistrationData();
    }
    
    public function index() {
        try {
            $today = Carbon::now()->format('Y-m-d');
            // $today = '2025-01-06';
            $data = Patient::query();
            $data->whereHas('patientRegistry', function($query) use ($today) {
                $query->where('mscAccount_Trans_Types', 5)  
                    ->where('isRevoked', 0)              
                    ->whereDate('registry_Date', $today);
                    // ->where('discharged_Date', '=', null);
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

    public function getPatientBroughtBy() {
        try {
            $data = mscPatientBroughtBy::select('id', 'description')->orderBy('id', 'ASC')->get();
            if($data->isEmpty()) return response()->json([], 404);
            $patientBroughtBy = $data->map(function($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($patientBroughtBy, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }

    public function getDisposition() {
        try {
            $data =  DB::connection('sqlsrv')->table('mscDispositions')
                ->select('id', 'disposition_description')
                ->orderBy('disposition_description','asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $dispositions = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->disposition_description,
                ];
            });
            return response()->json($dispositions, 200);
        } catch(\Exception $e) {
            return response()->json(['msg' => $e->getMessage()], 500);
        }
    }
    public function getComplaintList() {
        try {
            $data = mscComplaint::select('id', 'description')
                ->where('isActive', 1)
                ->orderBy('description', 'asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $mscComplaints = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($mscComplaints, 200);
        } catch(\Exception $e) {
            return response()->json(['msg'=> $e->getMessage()], 500);
        }
    }

    public function getServiceType() {
        try {
            $data = mscServiceType::select('id', 'description')
                ->where('isactive', 1)
                ->orderBy('description', 'asc')->get();
            if($data->isEmpty()) {
                return response()->json([], 404);
            }
            $mscServiceType = $data->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'description'   => $item->description
                ];
            });
            return response()->json($mscServiceType, 200);
        } catch(\Exception $e) {
            return response()->json(['msg'=> $e->getMessage()], 500);
        }
    }

    public function getrevokedemergencypatient() {
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

    public function revokepatient(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $patientRegistry = PatientRegistry::where('case_No', $id)->first();
            $patientRegistry->update([
                'isRevoked' => 1,
                'revokedBy' => Auth()->user()->idnumber,
                'revoked_date' => Carbon::now(),
                'revoked_remarks' => $request->payload['revoked_remarks'] ?? null,
                'revoked_hostname' => (new GetIP())->getHostname(),
                'UpdatedBy' => Auth()->user()->idnumber,
                'updated_at' => Carbon::now(),
            ]);
            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json([
                'message' => 'Patient revoked successfully',
                'patientRegistry' => $patientRegistry
            ], 200);
        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to revoke patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unrevokepatient(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $patientRegistry = PatientRegistry::where('patient_id', $id)->first();
            $patientRegistry->update([
                'isRevoked' => 0,
                'revokedBy' => null,
                'revoked_date' => null,
                'revoked_remarks' => null,
                'revoked_hostname' => null,
                'UpdatedBy' => Auth()->user()->idnumber,
                'updated_at' => Carbon::now(),
            ]);
            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json([
                'message' => 'Patient revoked successfully',
                'patientRegistry' => $patientRegistry
            ], 200);
        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to revoke patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAllergy($registry_id) {
        $allergy = PatientAllergies::where('case_No', $registry_id)->first();
        $isUpdated = false;
        if($allergy) {  
            $allergyUpdated           = $allergy->update(['isDeleted' => 1]);
            $causeOfAllergyUpdated    = $allergy->cause_of_allergy()->update(['isDeleted' => 1]);
            $symptomsOfAllergyUpdated = $allergy->symptoms_allergy()->update(['isDeleted' => 1]);
            $drugUseOfAllergyUpdated  = $allergy->drug_used_for_allergy()->update(['isDeleted' => 1]);
            if($allergyUpdated && $causeOfAllergyUpdated && $symptomsOfAllergyUpdated && $drugUseOfAllergyUpdated) {
                $isUpdated = true;
            }
        }
        return $isUpdated; 
    }
}