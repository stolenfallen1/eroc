<?php 

    namespace App\Helpers\HIS;

    use App\Models\BuildFile\SystemSequence;
    use App\Models\HIS\MedsysSeriesNo;
    use App\Models\HIS\services\Patient;
    use DB;

    class PatientRegistrySequence {
       
        protected $check_is_allow_medsys;
        public function __construct() {
            $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        }

        protected function handleExistingPatientData($request) {
            $existingPatient = Patient::where('lastname', $request->payload['lastname'])
            ->where('firstname', $request->payload['firstname'])
            ->first();
    
            return $existingPatient;
        }
        public function handleSequencesCreatePatient($request) {

            SystemSequence::where('code','MPID')->increment('seq_no');
            SystemSequence::where('code','MERN')->increment('seq_no');
            SystemSequence::where('code','MOPD')->increment('seq_no');
            SystemSequence::where('code','SERCN')->increment('seq_no');
    
            $sequence = SystemSequence::where('code', 'MPID')->select('seq_no', 'recent_generated')->first();
            $registry_sequence = SystemSequence::where('code', 'MERN')->select('seq_no', 'recent_generated')->first();
            $er_case_sequence = SystemSequence::where('code', 'SERCN')->select('seq_no', 'recent_generated')->first();
    
            if($this->check_is_allow_medsys) {
    
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('HospNum');
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');
                DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('ERNum');
    
                $check_medsys_series_no = MedsysSeriesNo::select('HospNum', 'ERNum', 'OPDId')->first();
    
                $patient_id     = $check_medsys_series_no->HospNum;
                $registry_id    = $check_medsys_series_no->OPDId;
                $er_Case_No     = $check_medsys_series_no->ERNum;
            
            } else {
            
                $patient_id             = $request->payload['patient_Id'] ?? intval($sequence->seq_no);
                $registry_id            = $request->payload['case_No'] ?? intval($registry_sequence->seq_no);
                $er_Case_No             = $request->payload['er_Case_No'] ?? intval($er_case_sequence->seq_no);
            }
    
            $existingPatient = $this->handleExistingPatientData($request);
            
            if ($existingPatient):
                $patient_id = $existingPatient->patient_Id;
            else:
                $sequence->where('code', 'MPID')->update([
                    'recent_generated'  => $patient_id
                ]);
    
                $registry_sequence->where('code', 'MERN')->update([
                    'recent_generated'  => $registry_id
                ]);
    
                $registry_sequence->where('code', 'MOPD')->update([
                    'recent_generated'  => $registry_id
                ]);
    
                $er_case_sequence->where('code', 'SERCN')->update([
                    'recent_generated'  => $er_Case_No
                ]);
                
            endif;
    
            return [
                'patientId'         => $patient_id,
                'registryId'        => $registry_id,
                'erCaseNo'          => $er_Case_No,
            ];
        }
    
        public function  handleSequenceUpdatePatient($request, $existingRegistry) {
    
            SystemSequence::where('code','MERN')->increment('seq_no');
            SystemSequence::where('code','MOPD')->increment('seq_no');
            SystemSequence::where('code','SERCN')->increment('seq_no');
    
            $registry_sequence = SystemSequence::where('code', 'MERN')->select('seq_no', 'recent_generated')->first();
            $er_case_sequence = SystemSequence::where('code', 'SERCN')->select('seq_no', 'recent_generated')->first();
    
            if(!$existingRegistry) {
    
                if($this->check_is_allow_medsys) {
    
                    DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('OPDId');
                    DB::connection('sqlsrv_medsys_patient_data')->table('tbAdmLastNumber')->increment('ERNum');
    
                    $check_medsys_series_no = MedsysSeriesNo::select('HospNum', 'ERNum', 'OPDId')->first();
                    $registry_id    = $check_medsys_series_no->OPDId;
                    $er_Case_No     = $check_medsys_series_no->ERNum;
    
                } else {
                    $registry_id = intval($registry_sequence->seq_no);
                    $er_Case_No  = intval($er_case_sequence->seq_no);
                }
    
                $registry_sequence->where('code', 'MERN')->update([
                    'recent_generated'  => $registry_id
                ]);
    
                $registry_sequence->where('code', 'MOPD')->update([
                    'recent_generated'  => $registry_id
                ]);
    
                $er_case_sequence->where('code', 'SERCN')->update([
                    'recent_generated'  => $er_Case_No
                ]);
    
            } else {
                $registry_id = $request->payload['case_No'];
                $er_Case_No  = $request->payload['er_Case_No'];
            } 
    
            return [
                'registryId'    => $registry_id,
                'erCaseNo'      => $er_Case_No
            ];
        }
    }