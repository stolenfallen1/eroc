<?php

namespace App\Http\Controllers\HIS\services\revoke_patient_registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use App\Models\User;
use \Carbon\Carbon;
use App\Helpers\GetIP;
use Illuminate\Support\Facades\DB;
class RevokePatientRegistration extends Controller
{
    //
    public function revokedPatientRegistration(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $patientRegistry = PatientRegistry::where('case_No', $id)->first();
            if(!$patientRegistry) {
                return response()->json([
                    'message' => 'Patient not found'
                ], 404);
            }
            if($patientRegistry->isRevoked == 1) {
                return response()->json([
                    'message' => 'Patient is already revoked'
                ], 404);
            }
            if($patientRegistry->mscAccount_Trans_Types === 5) {
                $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
                if(!$checkUser) {
                    return response()->json(['message' => 'Incorrect Username or Password'], 404);
                }
            }
            $is_revoked = $patientRegistry->update([
                'isRevoked' => 1,
                'revokedBy' => (int)$patientRegistry->mscAccount_Trans_Types === 5 
                                ? $checkUser->idnumber 
                                : Auth()->user()->idnumber,
                'revoked_Date' => Carbon::now(),
                'revoked_Remarks' => $request->payload['revoked_remarks'] ?? null,
                'revoked_Hostname' => (new GetIP())->getHostname(),
                'UpdatedBy' => (int)$patientRegistry->mscAccount_Trans_Types === 5 
                            ? $checkUser->idnumber 
                            : Auth()->user()->idnumber,
                'updated_at' => Carbon::now(),
            ]);
            if(!$is_revoked) {
                throw new \Exception('Failed to revoke patient');
            }
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

    public function unRevokedPatientRegistration(Request $request) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $patientRegistry = PatientRegistry::where('patient_Id', $request->payload['patient_Id'])
                                                ->where('case_No', $request->payload['case_No'])
                                                ->first();
            if(!$patientRegistry) {
                return response()->json([
                    'message' => 'Patient not found'
                ], 404);
            }
            if($patientRegistry->isRevoked == 0) {
                return response()->json([
                    'message' => 'Patient is not revoked'
                ], 404);
            }
            if($patientRegistry->mscAccount_Trans_types === 5) {
                $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
                if(!$checkUser) {
                    return response()->json(['message' => 'Incorrect Username or Password'], 404);
                }
                if($patientRegistry->revokedBy  !== $checkUser->idnumber) {
                    return response()->json([
                        'message' => 'You are not allowed to unrevoked this patient'
                    ], 403);
                }
            }

            if($patientRegistry->revokedBy  !== Auth()->user()->idnumber) {
                return response()->json([
                    'message' => 'You are not allowed to unrevoked this patient'
                ], 403);
            }
            
            $patientRegistry->update([
                'isRevoked' => 0,
                'revokedBy' => null,
                'revoked_Date' => null,
                'revoked_Remarks' => null,
                'revoked_Hostname' => null,
                'UpdatedBy' => (int)$request->payload['mscAccount_Trans_Types'] === 5 
                            ? $checkUser->idnumber 
                            : Auth()->user()->idnumber, 
                'updated_at' => Carbon::now(),
            ]);
            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json([
                'message' => 'Patient unrevoked successfully',
                'patientRegistry' => $patientRegistry
            ], 200);
        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to unrevoked patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
