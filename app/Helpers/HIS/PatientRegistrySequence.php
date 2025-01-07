<?php 

    namespace App\Helpers\HIS;

    use App\Models\BuildFile\SystemSequence;
    use App\Models\HIS\MedsysSeriesNo;
    use App\Models\HIS\services\Patient;
    use DB;
    use App\Helpers\HIS\SysGlobalSetting;

    class PatientRegistrySequence {
        protected $check_is_allow_medsys;
        public function __construct() {
            $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        }

        private function handleCDGPatientSeqNo() {
            SystemSequence::where('code','MPID')->increment('seq_no');
            $sequence = SystemSequence::where('code', 'MPID')->select('seq_no', 'recent_generated')->first();
            return $sequence->seq_no;
        }

        private function handleCDGEmergencyPatientCaseNo() {
            SystemSequence::where('code','MERN')->increment('seq_no');
            SystemSequence::where('code','MOPD')->increment('seq_no');
            $registry_sequence  = SystemSequence::where('code', 'MERN')->select('seq_no', 'recent_generated')->first();
            return $registry_sequence->seq_no;
        }

        private function handleCDGInPatientCaseNo() {
            SystemSequence::where('code', 'SIPCN')->increment('seq_no');
            $registry_sequence  = SystemSequence::where('code', 'SIPCN')->select('seq_no', 'recent_generated')->first();
            return $registry_sequence->seq_no;
        }

        private function handleCDGErCaseNo() {
            SystemSequence::where('code','SERCN')->increment('seq_no');
            $er_case_sequence   = SystemSequence::where('code', 'SERCN')->select('seq_no', 'recent_generated')->first();
            return $er_case_sequence->seq_no;
        }

        private function handleMedsysPatientSeqNo() {
            DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('HospNum');
            $check_medsys_series_no = MedsysSeriesNo::select('HospNum')->first();
            return $check_medsys_series_no->HospNum;
        }

        private function handleMedsysEmergencyPatientCaseNo() {
            DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');
            $check_medsys_series_no = MedsysSeriesNo::select('OPDId')->first();
            return $check_medsys_series_no->OPDId;
        }

        private function handleMedsysInPatientCaseNo() {
            DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('IDNum');
            $check_medsys_series_no = MedsysSeriesNo::select('IDNum')->first();
            return $check_medsys_series_no->IDNum;
        }

        private function handleMedsysErCaseNo() {
            DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('ERNum');
            $check_medsys_series_no = MedsysSeriesNo::select('ERNum')->first();
            return $check_medsys_series_no->ERNum;
        }

        public function handleEmergencyRegisterPatientSequences() {
            if($this->check_is_allow_medsys) {
                $patient_id     = $this->handleMedsysPatientSeqNo();
                $registry_id    = $this->handleMedsysEmergencyPatientCaseNo();
                $er_Case_No     = $this->handleMedsysErCaseNo();
            } else {
                $patient_id     = $this->handleCDGPatientSeqNo();
                $registry_id    = $this->handleCDGEmergencyPatientCaseNo();
                $er_Case_No     = $this->handleCDGErCaseNo();
            }
            SystemSequence::where('code', 'MPID')->update(['seq_no'  => $patient_id, 'recent_generated'   => $patient_id]);
            SystemSequence::where('code', 'MERN')->update(['seq_no'  => $registry_id, 'recent_generated'  => $registry_id]);
            SystemSequence::where('code', 'MOPD')->update(['seq_no'  => $registry_id , 'recent_generated' => $registry_id]);
            SystemSequence::where('code', 'SERCN')->update(['seq_no' => $er_Case_No, 'recent_generated'   => $er_Case_No]);
            return [
                'patientId'         => $patient_id,
                'registryId'        => $registry_id,
                'erCaseNo'          => $er_Case_No,
            ];
        }

        public function handleInPatientRegisterPatientSequences() {
            if($this->check_is_allow_medsys) {
                $patient_id     = $this->handleMedsysPatientSeqNo();
                $registry_id    = $this->handleMedsysInPatientCaseNo();
            } else {
                $patient_id     = $this->handleMedsysPatientSeqNo();
                $registry_id    = $this->handleCDGInPatientCaseNo();
            }
            SystemSequence::where('code', 'MPID')->update(['seq_no'   => $patient_id, 'recent_generated'  => $patient_id]);
            SystemSequence::where('code', 'SIPCN')->update(['seq_no'  => $registry_id,'recent_generated'  => $registry_id]);
            return [
                'patientId'         => $patient_id,
                'registryId'        => $registry_id,
            ];
        }

        public function handleUpdateEmergencyPatientSequences() {
            if($this->check_is_allow_medsys) {
                $registry_id    = $this->handleMedsysEmergencyPatientCaseNo();
                $er_Case_No     = $this->handleMedsysErCaseNo();
            } else {
                $registry_id    = $this->handleCDGEmergencyPatientCaseNo();
                $er_Case_No     = $this->handleCDGErCaseNo();
            }
            SystemSequence::where('code', 'MERN')->update(['seq_no'  => $registry_id, 'recent_generated' => $registry_id]);
            SystemSequence::where('code', 'MOPD')->update(['seq_no'  => $registry_id, 'recent_generated' => $registry_id]);
            SystemSequence::where('code', 'SERCN')->update(['seq_no' => $er_Case_No, 'recent_generated'  => $er_Case_No]);
            return [
                'registryId'        => $registry_id,
                'erCaseNo'          => $er_Case_No,
            ];
        }

        public function handleInPatientUpdateSequences() {
            if($this->check_is_allow_medsys) {
                $registry_id    = $this->handleMedsysInPatientCaseNo();
            } else {
                $registry_id    = $this->handleCDGInPatientCaseNo();
            }
            SystemSequence::where('code', 'SIPCN')->update(['seq_no'  => $registry_id, 'recent_generated'  => $registry_id]);
            return [
                'registryId'        => $registry_id
            ];
        }
    }