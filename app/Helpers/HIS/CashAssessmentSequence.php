<?php 

class CashAssessmentSequence 
{
    public function getSequence() 
    {
        // CDG_CORE - Global Charge Number (GCN) and Global Assessment Number (GAN)
        $GlobalChargeNumber = DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GCN')->value('seq_no');
        $GlobalAssementNumber = DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GAN')->value('seq_no');

        $MedSysCashSequence = DB::connection('sqlsrv_medsys_billing')->table('BILLING.dbo.tbAssessmentNum')->value('AssessmentNum');
        $MedSysLabSequence = DB::connection('sqlsrv_medsys_laboratory')->table('LABORATORY.dbo.tbLabSlip')->value('ChargeSlip');

        return [
            'CDG_CORE_GCN' => (int)$GlobalChargeNumber,
            'CDG_CORE_GAN' => (int)$GlobalAssementNumber,
            'MedSysCashSequence' => (int)$MedSysCashSequence,
            'MedSysLabSequence' => (int)$MedSysLabSequence,
        ];
    }

    public function incrementSequence() 
    {
        // CDG_CORE - Global Charge Number (GCN) and Global Assessment Number (GAN)
        DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GCN')->increment('seq_no');
        DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GCN')->increment('recent_generated');
        DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GAN')->increment('seq_no');
        DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GAN')->increment('recent_generated');

        // MedSys - Cash Assessment Number 
        DB::connection('sqlsrv_medsys_billing')->table('BILLING.dbo.tbAssessmentNum')->increment('AssessmentNum');
        // MedSys - Laboratory Charge Slip Number
        DB::connection('sqlsrv_medsys_laboratory')->table('LABORATORY.dbo.tbLabSlip')->increment('ChargeSlip'); 
    }
} 