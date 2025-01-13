<?php 

namespace App\Helpers\HIS;
use \Carbon\Carbon;
use App\Helpers\GetIP;
use App\Models\HIS\MedsysAdmittingCommunication;
use App\Models\HIS\AdmittingCommunicationFile;
use App\Models\HIS\MedsysERMaster;
use App\Models\HIS\MedsysOutpatient;

class DischargePatientHelper {

    public function cdgDischargedData($checkUser, $request, $patientRegistry) {
        return [
            'discharged_Userid'         => $checkUser->idnumber,
            'discharged_Date'           => Carbon::now(),
            'discharged_Hostname'       => (new GetIP())->getHostname(),
            'dischargeNotice_UserId'    => $patientRegistry->mgh_Userid,
            'dischargeNotice_Date'      => $patientRegistry->mgh_Datetime,
            'dischargeNotice_Hostname'  => $patientRegistry->mgh_Hostname,
            'discharge_Diagnosis'       => $request->payload['discharge_Diagnosis'] ?? null,
            'updatedBy'                 => $checkUser->idnumber,
            'updated_at'                => Carbon::now()
        ];
    }

    public function cdgDischargePatientForAdmission($checkUser, $patient_id, $case_No) {
        return [
            'patient_Id'        => $patient_id,
            'case_No'       => $case_No,
            'requestDate'   =>Carbon::now(),
            'requestBy'     => $checkUser->idnumber,
            'createdBy'     => $checkUser->idnumber,
            'createdat'     => Carbon::now(),
            'updatedby'     => $checkUser->idnumber,
            'updatedat'     => Carbon::now()
        ];
    }

    public function medsysDischargePatientForAdmission($checkUser, $patient_id, $OPDIDnum) {
        return [
            'HospNum'       => $patient_id,
            'OPDIDnum'      => $OPDIDnum,
            'RequestDate'   =>Carbon::now(),
            'RequestBy'     => $checkUser->idnumber 
        ];
    }

    public function dischargedPatientFromMedSysER($patient_id, $OPDIDnum) {
        return [
            'Hospnum'       => $patient_id,
            'IDnum'         => $OPDIDnum,
            'DcrDate'       => Carbon::now(),
        ];
    }

    public function dischargedPatientFromMedSysOutPatient($patient_id, $OPDIDnum) {
        return [
            'HospNum'       => $patient_id,
            'IDNum'         => $OPDIDnum,
            'DcrDate'       => Carbon::now(),
        ];
    }

    public function dischargedPatientForAdmission($patientRegistry, $checkUser, $request) {
        $patient_id = $patientRegistry->patient_Id;
        $case_No = $patientRegistry->case_No;
        $OPDIDnum = $patientRegistry->case_No . 'B';
        try {
            $medsysPatientDischarged = MedsysAdmittingCommunication::updateOrCreate(['OPDIDnum' => $OPDIDnum], $this->medsysDischargePatientForAdmission($checkUser, $patient_id, $OPDIDnum));
            $tbERPatientDischarged = MedsysERMaster::updateOrCreate(['IDnum' => $OPDIDnum], $this->dischargedPatientFromMedSysER($patient_id, $OPDIDnum));
            $tbOutPatientDischarged = MedsysOutpatient::updateOrCreate(['IDNum' => $OPDIDnum], $this->dischargedPatientFromMedSysOutPatient($patient_id, $OPDIDnum));
            if(!$medsysPatientDischarged || !$tbERPatientDischarged || !$tbOutPatientDischarged) {
                throw new \Exception('Failed to discharged Patient from MedSys');
            }  
            $discharged_patient = $patientRegistry->where('case_No',$case_No)->update($this->cdgDischargedData($checkUser, $request, $patientRegistry));
            $cdgPatientDischarged = AdmittingCommunicationFile::updateOrCreate(['case_No' => $case_No],  $this->cdgDischargePatientForAdmission($checkUser, $patient_id, $case_No));
            if(!$discharged_patient || !$cdgPatientDischarged) {
                throw new \Exception('Failed to discharged Patient from CDG');
            }
            return $discharged_patient;
        } catch (\Exception $e) {   
            throw new \Exception('Failed to discharged Patient: ' . $e->getMessage());
        }
    }

    public function processDischargedPatient($patientRegistry, $checkUser, $request) {
        $patient_id = $patientRegistry->patient_Id;
        $case_No = $patientRegistry->case_No;
        $OPDIDnum = $patientRegistry->case_No . 'B';
        try {
            $discharged_patient = $patientRegistry->where('case_No', $case_No)->update($this->cdgDischargedData($checkUser, $request, $patientRegistry));
            $tbERPatientDischarged = MedsysERMaster::updateOrCreate(['IDnum' => $OPDIDnum], $this->dischargedPatientFromMedSysER($patient_id, $OPDIDnum));
            $tbOutPatientDischarged = MedsysOutpatient::updateOrCreate(['IDNum' => $OPDIDnum], $this->dischargedPatientFromMedSysOutPatient($patient_id, $OPDIDnum));
            if(!$discharged_patient || !$tbERPatientDischarged || !$tbOutPatientDischarged) {
                throw new \Exception('Failed to discharged Patient');
            }
            return $discharged_patient;
        } catch (\Exception $e) {
            throw new \Exception('Failed to discharged Patient: ' . $e->getMessage());
        }
    }
}