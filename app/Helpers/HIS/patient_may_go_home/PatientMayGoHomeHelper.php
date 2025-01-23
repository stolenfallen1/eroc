<?php 
namespace App\Helpers\HIS\patient_may_go_home;
use \Carbon\Carbon;
use App\Helpers\GetIP;

class PatientMayGoHomeHelper {
    public function savePatientData($request, $checkUser) {
        return [
            'queue_Number'              => 0,
            'mscDisposition_Id'         => $request->payload['mscDisposition_Id'],
            'mscCase_Result_Id'         => $request->payload['ERpatient_result'],
            'mgh_Userid'                => $checkUser->idnumber,
            'mgh_Datetime'              => Carbon::now(),
            'mgh_Hostname'              => (new GetIP())->getHostname(),
            'isreferredFrom'            => intval($request->mscDisposition_Id) === 3 ? 1 : 0,
            'referred_From_HCI'         => $request->payload['refered_Form_HCI'] ?? null,
            'referred_From_HCI_address' => $request->payload['FromHCIAddress'] ?? null,
            'referred_From_HCI_code'    => $request->payload['refered_From_HCI_code'] ?? null,
            'referred_To_HCI'           => $request->payload['refered_To_HCI'] ?? null,
            'referred_To_HCI_address'   => $request->payload['ToHCIAddress'] ?? null,
            'referred_To_HCI_code'      => $request->payload['refered_To_HCI_code'] ?? null,
            'referring_Doctor'          => $request->payload['refering_Doctor'] ?? null,
            'referral_Reason'           => $request->payload['referal_Reason'] ?? null,
            'typeOfDeath_id'            => $request->payload['typeOfDeath_id'] ?? null,
            'dateOfDeath'               => $request->payload['dateOfDate'] ?? null,
            'updatedBy'                 => $checkUser->idnumber,
            'updated_at'                => Carbon::now()
        ];
    }
    public function patientHistoryData($request, $checkUser) {
        return [
            'impression'            => $request->payload['initial_impression'] ?? null,
            'discharge_Diagnosis'   => $request->payload['discharge_diagnosis'] ?? null,
            'updatedby'             => $checkUser->idnumber,
            'updated_at'            => Carbon::now()
        ];
    }

    public function saveUntagPatientData($checkUser) {
        return [
            'queue_Number'          => 0,
            'mscDisposition_Id'     => null,
            'mgh_Userid'            => null,
            'mgh_Datetime'          => null,
            'mgh_Hostname'          => null,
            'untag_Mgh_Userid'      => $checkUser->idnumber,
            'untag_Mgh_Datetime'    => Carbon::now(),
            'untag_Mgh_Hostname'    => (new GetIP())->getHostname(),
            'updatedBy'             => $checkUser->idnumber,
            'updated_at'            => Carbon::now()
        ];
    }
    public function processPatientTagForMayGoHome($request, $checkUser, $patient_registry, $id) {
        $updated_registry   = $patient_registry->where('case_No', $id)->update($this->savePatientData($request, $checkUser));
        $updated_history    = $patient_registry->history()->where('case_No', $id)->update($this->patientHistoryData($request, $checkUser));
        if(!$updated_registry && !$updated_history) {
            throw new \Exception('Error');
        } 
    }

    public function processPatientUntagForMayGoHome($request, $checkUser, $patient_registry, $id) {
        $updated_registry   = $patient_registry->where('case_No', $id)->update($this->saveUntagPatientData($checkUser));
        if(!$updated_registry) {
            throw new \Exception('Error');
        }
    }
}