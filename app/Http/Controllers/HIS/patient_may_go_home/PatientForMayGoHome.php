<?php

namespace App\Http\Controllers\HIS\patient_may_go_home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\services\PatientRegistry;
use App\Models\User;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\Profesional\Doctors;
use App\Models\HIS\ErResult;
use App\Models\HIS\mscPatientStatus;
use DB;
use App\Helpers\HIS\patient_may_go_home\PatientMayGoHomeHelper;

class PatientForMayGoHome extends Controller
{
    protected $check_is_allow_medsys;
    protected $patient_may_go_home;
    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->patient_may_go_home = new PatientMayGoHomeHelper();
    }
    public function mayGoHome(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        try {
            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            if(!$checkUser):
                return response()->json([$message='Incorrect Username or Password'], 404);
            endif;
            $patient_registry = PatientRegistry::where('case_No', $id)->first();

            if($patient_registry) {
                $this->patient_may_go_home->processPatientTagForMayGoHome($request, $checkUser, $patient_registry, $id);
            }
            DB::connection('sqlsrv_patient_data')->commit();
            return response()->json([
                'message' => 'Patient is tagged as may go home successfully'
            ], 200);
        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            return response()->json([
                'message' => 'Failed to update patient registry or patient history'
            ], 500);
        }
    }

    public function untagMGH(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        try {
            $checkUser = User::where([
                ['idnumber', '=', $request->payload['user_userid']], 
                ['passcode', '=', $request->payload['user_passcode']]
            ])->first();
            if (!$checkUser) {
                return response()->json(['message' => 'Incorrect Username or Password'], 404);
            }
            $patientRegistry = PatientRegistry::where('case_No', $id)->first();
            if (!$patientRegistry) {
                throw new \Exception('Patient registry not found.');
            }
            $hasMGH = !empty($patientRegistry->mgh_Userid) && !empty($patientRegistry->mgh_Datetime);
            $isNotDischarged = empty($patientRegistry->discharged_Userid) && empty($patientRegistry->discharged_Date);
            if($hasMGH && $isNotDischarged) {
                $this->patient_may_go_home->processPatientUntagForMayGoHome($request, $checkUser, $patientRegistry, $id);
                DB::connection('sqlsrv_patient_data')->commit();
                DB::connection('sqlsrv_medsys_patient_data')->commit();
                return response()->json(['message' => 'Patient discharge approval has been successfully canceled.'], 200);
            }
            else {
                return response()->json(['message' => 'Patient is not been tagged yet for May Go Home and or Patient already has a discharged order']);
            } 
        } catch(\Exception $e) {
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();
            return response()->json(['message' => 'Error! ' . $e->getMessage()], 500);
        }
    }

    public function getDoctorsList() {
        try {
            $query = Doctors::select('id', 'doctor_code', 'lastname', 'firstname', 'middlename')
                            ->where('isactive', 1);
            if (Request()->keyword) {
                $query->where('lastname', 'LIKE', '%' . Request()->keyword . '%')
                    ->orWhere('firstname', 'LIKE', '%' . Request()->keyword . '%')
                    ->orWhere('doctor_code', 'LIKE', '%' . Request()->keyword . '%');
            }
            $query->orderBy('id', 'asc');
            $page = Request()->per_page ?? '50';
            return response()->json($query->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
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

    public function getPatientStatusList() {
        try {
            $patientStatus = mscPatientStatus::select('id', 'description')
                                                ->where('isactive', 1)
                                                ->orderBy('id', 'asc')
                                                ->get();
            return response()->json($patientStatus, 200);
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPatientChargesStatus($id) {
        try {
            $patientRegistry = PatientRegistry::where('case_No', $id)->first();
            if(!$patientRegistry) {
                return response()->json(['message' => 'Record Not Found'], 404);
            } else {
                $hasMGH = $this->checkHasMGH($patientRegistry);
                if($hasMGH) {
                    $isTagAsMGH = [
                        'isMayGoHome' => 1
                    ]; 
                    return response()->json($isTagAsMGH, 200);
                } else {
                    $dataCharges = $this->getPendingCharges($id);
                    return response()->json($dataCharges, 200);
                }
            }
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    private function checkHasMGH($patientRegistry) {
        return !empty($patientRegistry->mgh_Userid) && !empty($patientRegistry->mgh_Datetime);
    }

    private function getPendingCharges($id) {
        $queryCashAssessment = DB::table('CDG_BILLING.dbo.CashAssessment as ca')
            ->select('ca.patient_Id', 'ca.case_No', 'ca.revenueID as revenue_Id', 'ca.recordStatus', 'ca.ORNumber')
            ->where('ca.case_No', '=', $id);

        $queryNurseLogBook = DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
            ->select('cdgLB.patient_Id', 'cdgLB.case_No', 'cdgLB.revenue_Id', 'cdgLB.record_Status as recordStatus', DB::raw('NULL as ORNumber'))
            ->where('cdgLB.case_No', '=', $id);
        return $queryCashAssessment->union($queryNurseLogBook)->get();
    }
}
