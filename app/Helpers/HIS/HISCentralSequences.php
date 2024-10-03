<?php 

    namespace App\Helpers\HIS;

    use App\Models\BuildFile\SystemCentralSequences;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;

    class HISCentralSequences {

        public function getSequences($patientType) {

            $sequences = DB::select('EXEC CDG_PATIENT_DATA.dbo.spUpdateMedsysSequence ?', [$patientType]);

            return $sequences;
        }

        public function runERUpdateSequences() {

            $flag = false;

            DB::connection('sqlsrv')->beginTransaction();

            try {

                $updatedMPID = SystemCentralSequences::where('code', 'MPID')->update([

                    'seq_no' => DB::raw('(SELECT hospnum FROM CDGHIS.patient_data.dbo.tbadmlastnumber) - 1'),
                    'recent_generated' => DB::raw('(SELECT hospnum FROM CDGHIS.patient_data.dbo.tbadmlastnumber)')

                ]);

                $updatedMERN = SystemCentralSequences::where('code', 'MERN')->update([

                    'seq_no' => DB::raw('(SELECT ERNum FROM CDGHIS.patient_data.dbo.tbadmlastnumber) + 1'),
                    'recent_generated' => DB::raw('(SELECT ERNum FROM CDGHIS.patient_data.dbo.tbadmlastnumber)')
                    
                ]);

                if($updatedMPID && $updatedMERN) {

                    DB::connection('sqlsrv')->commit();
                    $flag = true;

                } 

            } catch (\Exception $e) {

                DB::connection('sqlsrv')->rollBack();
                \Log::error('Failed to update ER sequences: ' . $e->getMessage());

            }

            return $flag;

        }

        public function runOPDUpdateSequences() {

            $flag = false;

            DB::connection('sqlsrv')->beginTransaction();

            try {

                $updatedMOPD = SystemCentralSequences::where('code', 'MOPD')->update([

                    'seq_no'  => DB::raw('(SELECT OPDId FROM CDGHIS.patient_data.dbo.tbadmlastnumber) -1 '),
                    'recent_generated' => DB::raw('(SELECT OPDId FROM CDGHIS.patient_data.dbo.tbadmlastnumber)')
    
                ]);
    
                $updatedMIPN = SystemCentralSequences::where('code', 'MIPN')->update([
    
                    'seq_no'  => DB::raw('(SELECT IDNum FROM CDGHIS.patient_data.dbo.tbadmlastnumber) -1 '),
                    'recent_generated' => DB::raw('(SELECT IDNum FROM CDGHIS.patient_data.dbo.tbadmlastnumber)')
    
                ]);

                if($updatedMOPD &&  $updatedMIPN) {

                    DB::connection('sqlsrv')->commit();
                    $flag = true;

                } 

            } catch(\Exception $e) {

                DB::rollBack();
                \Log::error('Failed to update OPD sequences: ' . $e->getMessage());
                
            }

            return $flag;

        }
    }
