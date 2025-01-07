<?php 

namespace App\Helpers\HIS;
use Carbon\Carbon;
use App\Models\HIS\MedsysERMaster;
use App\Models\HIS\MedsysPatientMaster;
use App\Models\HIS\MedsysOutpatient;
use App\Models\HIS\medsys\TbPatient;
use Illuminate\Support\Facades\Log;

class PatientRegistryObserverHelper {

    private function handleTbERMasterData($patientRegistry) {
        return [
            'Hospnum'                   =>  $patientRegistry->patient_Id,
            'IDnum'                     =>  $patientRegistry->case_No . 'B',
            'ERNum'                     =>  $patientRegistry->er_Case_No,
            'AdmDate'                   =>  $patientRegistry->AdmDate
                                        ?   $patientRegistry->AdmDate 
                                        :   Carbon::now(),
            'DcrDate'                   =>  $patientRegistry->discharged_Date,
            'AccountNum'                =>  $patientRegistry->guarantor_Id   
                                        ?   $patientRegistry->guarantor_Id 
                                        :   $patientRegistry->patient_Id,
            'OpdStatus'                 => 'E',
            'OpdType'                   => 'O',
            'HosPlan'                   => 'C',
            'ServiceID1'                => 10,
            'PatientClassification'     => 'C',
            'DischargeRemarks'          => $patientRegistry->discharge_Diagnosis ?? '',
            'isEmergencyCase'           => 1,
            'DoctorID1'                 => $patientRegistry->attending_Doctor,
            'ReasonOfReferral'          =>  $patientRegistry->referral_Reason,
            'ReferredFrom'              =>  $patientRegistry->referred_From_HCI,
            'BEDNUMBER'                 =>  $patientRegistry->er_Bedno,
            'ReferredTo'                =>  $patientRegistry->referred_To_HCI

        ];
    }

    private function handleTBOutPatientData($patientRegistry) {
        return [
            'HospNum'       =>  $patientRegistry->patient_Id,
            'IDNum'         =>  $patientRegistry->case_No . 'B',
            'ERNum'         =>  $patientRegistry->er_Case_No,
            'AdmDate'       =>  $patientRegistry->registry_Date  
                            ?   $patientRegistry->registry_Date 
                            :   Carbon::now(),
            'ServiceID1'    => 10,          
            'OpdStatus'     => 'E',
            'OpdType'       => 'O',
            'HosPlan'       => $patientRegistry->mscPrice_Schemes == 1 ? 'P' : 'C',
            'PatientType'   => 'C',
            'DoctorID1'     => $patientRegistry->attending_Doctor ?? '',                
            'AccountNum'    =>  $patientRegistry->guarantor_Id   
                            ?   $patientRegistry->guarantor_Id 
                            :   $patientRegistry->patient_Id,
            'UserID'        => $patientRegistry->updatedBy
        ];
    }

    private function handleAdmittedPatientData($patientRegistry) {
        return [
            'HospNum'           =>  $patientRegistry->patient_Id,
            'IdNum'             =>  $patientRegistry->case_No,
            'AccountNum'        =>  $patientRegistry->guarantor_Id   
                                ?   $patientRegistry->guarantor_Id 
                                :   $patientRegistry->patient_Id,   
            'HospPlan'          => $patientRegistry->mscPrice_Schemes == 1 ? 'P' : 'C',
            'MedicareType'      => '',
            'AdmType'           => '',
            'AdmDate'           => $patientRegistry->registry_Date,
            'DcrDate'           => $patientRegistry->discharged_Date,
            'ServiceID'         => 10,          
            'UserID'            => $patientRegistry->updatedBy
        ];
    }

    private function handleUpdateGuarantor($patientRegistry) {
        return [
            'AccountNum'    => $patientRegistry->guarantor_Id ?? $patientRegistry->patient_Id,
        ];
    }

    public function processRequestToSaveDataInPatientMaster($patientRegistry) {
        try {
            $patientData = MedsysPatientMaster::updateOrCreate(
                [
                    'HospNum' => $patientRegistry->patient_Id
                ], 
                $this->handleUpdateGuarantor($patientRegistry)
            );
            if(!$patientData) {
                throw new \Exception('Error saving data in tbmaster');
            }
            return $patientData;
        } catch (\Exception $e) {
            Log::error('Error saving data in tbmaster:', ['error' => $e->getMessage()]);
            throw new \Exception('Error saving data in tbmaster: ' . $e->getMessage());
        }
    }

    public function processRequestToSaveDataInTbERMaster($patientRegistry) {
        $erId = $patientRegistry->case_No . 'B';
        try {
            $patientData = MedsysERMaster::updateOrCreate(
                [
                    'Hospnum' => $patientRegistry->patient_Id,
                    'IDnum' => $erId
                ], 
                $this->handleTbERMasterData($patientRegistry)
            );
            if(!$patientData) {
                throw new \Exception('Error saving data in tbERMaster');
            }
            return $patientData;
        } catch (\Exception $e) {
            Log::error('Error saving data in tbERMaster:', ['error' => $e->getMessage()]);
            throw new \Exception('Error saving data in tbERMaster: ' . $e->getMessage());
        }
    }

    public function processRequestToSaveDataInTbOutPatient($patientRegistry) {
        $erId = $patientRegistry->case_No . 'B';
        try {
            $outPatientData = MedsysOutpatient::updateOrCreate(
                [
                    'HospNum' => $patientRegistry->patient_Id,
                    'IDNum' => $erId
                ], 
                $this->handleTBOutPatientData($patientRegistry)
            );
            if(!$outPatientData) {
                throw new \Exception('Error saving data in tbOutPatient');
            }
            return $outPatientData;
        } catch (\Exception $e) {
            Log::error('Error saving data in tbOutPatient:', ['error' => $e->getMessage()]);
            throw new \Exception('Error saving data in tbOutPatient: ' . $e->getMessage());
        }
    }
    public function processRequestToSaveDataInTbPatient($patientRegistry) {
        try {
            $patientData = TbPatient::updateOrCreate(
                [
                    'HospNum' => $patientRegistry->patient_Id,
                    'IdNum' => $patientRegistry->case_No
                ], 
                $this->handleAdmittedPatientData($patientRegistry)
            );
            if(!$patientData) {
                throw new \Exception('Error saving data in tbAdmittedPatient');
            }
            return $patientData;
        } catch (\Exception $e) {
            Log::error('Error saving data in tbAdmittedPatient:', ['error' => $e->getMessage()]);
            throw new \Exception('Error saving data in tbAdmittedPatient: ' . $e->getMessage());
        }
    }
}