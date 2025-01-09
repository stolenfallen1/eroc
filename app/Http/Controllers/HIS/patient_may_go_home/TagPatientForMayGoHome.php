<?php

namespace App\Http\Controllers\HIS\patient_may_go_home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\services\PatientRegistry;
use App\Models\User;
use App\Helpers\HIS\SysGlobalSetting;
use DB;
use App\Helpers\HIS\patient_may_go_home\PatientMayGoHomeHelper;

class TagPatientForMayGoHome extends Controller
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
}
