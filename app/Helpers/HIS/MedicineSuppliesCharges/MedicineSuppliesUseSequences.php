<?php 

    namespace App\Helpers\HIS\MedicineSuppliesCharges;
    use DB;

    class MedicineSuppliesUseSequences {
        public function handleTbNursePHSlipSequence() {
            DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->increment('ChargeSlip');
            $tbNursePHSlipSequence = DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->first();
            return $tbNursePHSlipSequence->ChargeSlip;
        }

        public function handleTbInvChargeSlipSequence() {
            DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->increment('DispensingCSlip');
            $tbInvChargeSlipSequence = DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->first();
            return $tbInvChargeSlipSequence->DispensingCSlip;
        }

        public function handleMedsysCashAssessmentSequence() {
            DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->increment('AssessmentNum');
            DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->increment('RequestNum');
            $medsysCashAssessmentSequence = DB::connection('sqlsrv_medsys_billing')->table('Billing.dbo.tbAssessmentNum')->first();
            return [
                'AssessmentNum' => $medsysCashAssessmentSequence->AssessmentNum,
                'RequestNum' => $medsysCashAssessmentSequence->RequestNum
            ];
        }
    }