<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\services\PatientRegistry;
use App\Models\User;
use App\Helpers\GetIP;
use Illuminate\Http\Request;
use \Carbon\Carbon;
use App\Models\HIS\ErResult;
use DB;

class PatientDischarge extends Controller
{
    //
    public function mayGoHome(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {

            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            if(!$checkUser):
                return response()->json([$message='Incorrect Username or Password'], 404);
            endif;

            $registry_data = [
                'queue_Number'      => 0,
                'mscDisposition_Id' => $request->payload['mscDisposition_Id'],
                'mgh_Userid'        => $checkUser->idnumber,
                'mgh_Datetime'      => Carbon::now(),
                'mgh_Hostname'      => (new GetIP())->getHostname(),
            ];

            $history_data = [
                'impression'            => $request->payload['initial_impression'],
                'discharge_Diagnosis'   => $request->payload['discharge_diagnosis']
            ];

            $patient_registry = PatientRegistry::where('case_No', $id)->first();

            if($patient_registry) {
                $updated_registry   = $patient_registry->update($registry_data);
                $updated_history    = $patient_registry->history()->update($history_data);
                if(!$updated_registry && !$updated_history) {
                    throw new \Exception('Error');
                } 
                DB::connection('sqlsrv_patient_data')->commit();
                return response()->json([
                    'message' => 'Patient is tagged as may go home successfully'
                ], 200);
            }

        } catch(\Exception $e) {
            
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to update patient registry or patient history'
            ], 500);
        }
    }


    public function dischargePatient(Request $request, $id) {

        DB::connection('sqlsrv_patient_data')->beginTransaction();

        try {

            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            
            if(!$checkUser):
                return response()->json([$message='Incorrect Username or Password'], 404);
            endif;

            $isTagMGH = PatientRegistry::where('case_No', $id)->first();

            if(!$isTagMGH && $isTagMGH->mgh_Userid === '') {
                return response()->json([
                    'message' => 'This patient has not yet been tagged as eligible for discharge.'
                ], 404);
            }

            $registry_data = [
                'discharged_Userid'         => $checkUser->idnumber,
                'discharged_Date'           => Carbon::now(),
                'discharged_Hostname'       => (new GetIP())->getHostname(),
                'dischargeNotice_UserId'    => $isTagMGH->mgh_Userid,
                'dischargeNotice_Date'      => $isTagMGH->mgh_Datetime,
                'dischargeNotice_Hostname'  => $isTagMGH->mgh_Hostname,
                'discharge_Diagnosis'       => $request->payload['discharge_Diagnosis'],
                'updatedBy'                 => $checkUser->idnumber,
                'updated_at'                => Carbon::now()
            ];

            $patient_registry = PatientRegistry::where('case_No', $id)->first();
            
            if($patient_registry) {

                $discharged_patient = $patient_registry->update($registry_data);

                if(!$discharged_patient) {
                    throw new \Exception('Error');
                }
            }

            DB::connection('sqlsrv_patient_data')->commit();

            return response()->json([
                'message' => 'Patient has been discharged successfuly'
            ], 200);

        } catch(\Exception $e) {

            DB::connection('sqlsrv_patient_data')->rollBack();

            return response()->json([
                'message' => 'Failed to Discharged Patient, Pleas call IT Department'
            ], 500);
        }
       
    }

    public function erResult() {
        try {
            $data = ErResult::select('id', 'description')
                            ->orderBy('description','asc')
                            ->get();
            if($data->isEmpty()) {
                return response()->json([
                    'message'   => 'No data is Found!'
                ], 404);
            }

            return response()->json($data, 200);

        } catch(\Exception $e) {
            return response()->json([
                'message'   => 'Error' . $e->getMessage()
            ], 500);
        }
    }
}
