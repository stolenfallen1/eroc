<?php 

class GlobalChargingSequences 
{
    public function getSequence() 
    {
        // CDG_CORE - Global Charge Number (GCN) and Global Assessment Number (GAN)
        $GlobalChargeNumber = DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GCN')->value('seq_no');
        $GlobalAssementNumber = DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GAN')->value('seq_no');

        // MedSys - Cash Assessment Number
        $MedSysCashSequence = DB::connection('sqlsrv_medsys_billing')->table('BILLING.dbo.tbAssessmentNum')->value('AssessmentNum');
        // MedSys - Laboratory Charge Slip Number
        $MedSysLabSequence = DB::connection('sqlsrv_medsys_laboratory')->table('LABORATORY.dbo.tbLabSlip')->value('ChargeSlip');
        // MedSys - Xray and UltraSound Charge Slip Number
        $MedSysXrayUltraSoundSequence = DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->whereIn('RevenueID', ['XR', 'US'])
            ->value('ChargeSlipNum');
        // MedSys- CT Scan Charge Slip Number
        $MedSysCTScanSequence = DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'CT')
            ->value('ChargeSlipNum');
        // MedSys - MRI Charge Slip Number
        $MedSysMRISequence = DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'MI')
            ->value('ChargeSlipNum');
        // MedSys - Mammography Charge Slip Number
        $MedSysMammoSequence = DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'MM')
            ->value('ChargeSlipNum');
        // MedSys - Centre for Women Charge Slip Number
        $MedSysCentreForWomenSequence = DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'WC')
            ->value('ChargeSlipNum');
        // MedSys - Nuclear Medicine Charge Slip Number
        $MedSysNuclearMedSequence = DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'NU')
            ->value('ChargeSlipNum');

        return [
            'CDG_CORE_GCN'                  => (int)$GlobalChargeNumber,
            'CDG_CORE_GAN'                  => (int)$GlobalAssementNumber,
            'MedSysCashSequence'            => (int)$MedSysCashSequence,
            'MedSysLabSequence'             => (int)$MedSysLabSequence,
            'MedSysXrayUltraSoundSequence'  => (int)$MedSysXrayUltraSoundSequence,
            'MedSysCTScanSequence'          => (int)$MedSysCTScanSequence,
            'MedSysMRISequence'             => (int)$MedSysMRISequence,
            'MedSysMammoSequence'           => (int)$MedSysMammoSequence,
            'MedSysCentreForWomenSequence'  => (int)$MedSysCentreForWomenSequence,
            'MedSysNuclearMedSequence'      => (int)$MedSysNuclearMedSequence,
        ];
    }
    public function incrementSequence($revenueID = null) 
    {
        if ($revenueID === null) {
            $this->incrementMedSysCashSequence();
        } else {
            switch ($revenueID) {
                case 'LB':
                    $this->incrementMedSysLabSequence();
                    break;
                case 'XR':
                case 'US':
                    $this->incrementMedSysXrayUltraSoundSequence();
                    break;
                case 'CT':
                    $this->incrementMedSysCTScanSequence();
                    break;
                case 'MI':
                    $this->incrementMedSysMRISequence();
                    break;
                case 'MM':
                    $this->incrementMedSysMammoSequence();
                    break;
                case 'WC':
                    $this->incrementMedSysCentreForWomenSequence();
                    break;
                case 'NU':
                    $this->incrementMedSysNuclearMedSequence();
                    break;
            }
        }
    }
    protected function incrementGlobalCoreSequences() 
    {
        // CDG_CORE - Global Charge Number (GCN) and Global Assessment Number (GAN)
        DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GCN')->increment('seq_no');
        DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GCN')->increment('recent_generated');
        DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GAN')->increment('seq_no');
        DB::connection('sqlsrv')->table('CDG_CORE.dbo.sysCentralSequences')->where('code', 'GAN')->increment('recent_generated');
    }
    protected function incrementMedSysCashSequence() 
    {
        // MedSys - Cash Assessment Number 
        DB::connection('sqlsrv_medsys_billing')->table('BILLING.dbo.tbAssessmentNum')->increment('AssessmentNum');
    }
    protected function incrementMedSysLabSequence() 
    {
        // MedSys - Laboratory Charge Slip Number
        DB::connection('sqlsrv_medsys_laboratory')->table('LABORATORY.dbo.tbLabSlip')->increment('ChargeSlip');
    }
    protected function incrementMedSysXrayUltraSoundSequence() 
    {
        // MedSys - Xray and UltraSound Charge Slip Number
        DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->whereIn('RevenueID', ['XR', 'US'])
            ->increment('ChargeSlipNum');
    }
    protected function incrementMedSysCTScanSequence() 
    {
        // MedSys - CT Scan Charge Slip Number
        DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'CT')
            ->increment('ChargeSlipNum');
    }
    protected function incrementMedSysMRISequence() 
    {
        // MedSys - MRI Charge Slip Number
        DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'MI')
            ->increment('ChargeSlipNum');
    }
    protected function incrementMedSysMammoSequence() 
    {
        // MedSys - Mammography Charge Slip Number
        DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'MM')
            ->increment('ChargeSlipNum');
    }
    protected function incrementMedSysCentreForWomenSequence() 
    {
        // MedSys - Centre for Women Charge Slip Number
        DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'WC')
            ->increment('ChargeSlipNum');
    }
    protected function incrementMedSysNuclearMedSequence() 
    {
        // MedSys - Nuclear Medicine Charge Slip Number
        DB::connection('sqlsrv_medsys_radiology')->table('RADIOLOGY.dbo.tbradiologyrevenues')
            ->where('RevenueID', 'NU')
            ->increment('ChargeSlipNum');
    }
} 