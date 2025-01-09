<?php
    namespace App\Helpers\HIS\MedicineSuppliesCharges;
    use App\Models\HIS\his_functions\NurseLogBook;
    use App\Models\HIS\medsys\tbInvStockCard;
    use App\Models\HIS\medsys\tbNurseLogBook;
    use App\Models\MMIS\inventory\InventoryTransaction;
    use App\Models\HIS\MedsysCashAssessment;
    use App\Models\HIS\his_functions\CashAssessment;
    use App\Models\HIS\his_functions\HISBillingOut;
    use App\Models\HIS\medsys\MedSysDailyOut;
    use Exception;
    use \Carbon\Carbon;
    use App\Helpers\HIS\MedicineSuppliesCharges\MedicineSuppliesPrepareData;
    
    class CancelChargeItemsSupportProcess {
        protected $medicineSuppliesData;
        public function __construct() {
            $this->medicineSuppliesData = new MedicineSuppliesPrepareData();
        }
        public function processRevokedBillingout($request, $billingOut_items, $medsys_dailyOut_items, $checkUser, $reference_id) {
            $is_updated_billingOut = HISBillingOut::where('refNum',  $reference_id)
                ->update(
                    [
                        'refNum' => $reference_id . '[REVOKED]',
                        'ChargeSlip' => $reference_id . '[REVOKED]',
                    ]
                );
    
            $is_updated_medsys_billingOut = MedSysDailyOut::where('RefNum', $reference_id)
                ->update(
                    [
                        'RefNum' => $reference_id . '[REVOKED]',
                        'ChargeSlip' => $reference_id . '[REVOKED]',
                    ]
                ); 
            if(!$is_updated_billingOut || !$is_updated_medsys_billingOut) {
                throw new Exception('Failed to revoke charges');
            }
            foreach ($billingOut_items as $item) {
                $is_created_billing = HISBillingOut::create($this->medicineSuppliesData->BillingOutData($request, $item, $checkUser));
                if(!$is_created_billing) {
                    throw new Exception('Failed to revoke charges');
                }
            }
            foreach ($medsys_dailyOut_items as $item) {
                $is_created_medsys_dailyOut = MedSysDailyOut::create($this->medicineSuppliesData->MedsysBillingOutData($request, $item, $checkUser));
                if(!$is_created_medsys_dailyOut) {
                    throw new Exception('Failed to revoke charges');
                }
            }
        }
    
        public function processRevokedHMOCharges($request, $checkUser, $cdg_mmis_inventory_items, $medsys_inventory_items, $reference_id) {
            $isUpdated_cdg_lb  = NurseLogBook::where('referenceNum', $reference_id)->update(['record_Status' => 'R']);
            $isUpdated_medsys_lb  = tbNurseLogBook::where('ReferenceNum', $reference_id)->update(['RecordStatus' => 'R']);
            if(!$isUpdated_cdg_lb || !$isUpdated_medsys_lb) {
                throw new Exception('Failed to revoke charges');
            }
            foreach ($cdg_mmis_inventory_items as $item) {
                $isCreated_cdg_MMIS = InventoryTransaction::create($this->medicineSuppliesData->CDGMMISInventoryData($item, $checkUser));
                if(!$isCreated_cdg_MMIS) {
                    throw new Exception('Failed to revoke charges');
                }
            }
            foreach ($medsys_inventory_items as $item) {
                $isCreated_medsys_Inventory  = tbInvStockCard::create($this->medicineSuppliesData->MedsysStockCardData($request, $item, $checkUser));
                if(!$isCreated_medsys_Inventory) {
                    throw new Exception('Failed to revoke charges');
                }
            }
        }
    
        public function processRevokedSelfPayCharges($request, $checkUser, $getCashAssessment_items, $getMedsysCashAssessment_items, $reference_id) {
            $isUpdatedRow = CashAssessment::where('refNum', $reference_id)->update([
                'recordStatus'  => 'R',
                'dateRevoked'   => Carbon::now(),
                'revokedBy'     => $checkUser->idnumber,
            ]);
            $isMedysUpdatedRow = MedsysCashAssessment::where('RefNum', $reference_id)->update([
                'RecordStatus'  => 'R',
                'DateRevoked'   => Carbon::now(),
                'RevokedBy'     => $checkUser->idnumber
            ]);
            if(!$isUpdatedRow || !$isMedysUpdatedRow) {
                throw new Exception('Failed to revoke charges');
            }
            foreach ($getCashAssessment_items as $item) {
                $isRowCreated = CashAssessment::create($this->medicineSuppliesData->CDGCashAssessmentData($request, $item, $checkUser));
                if(!$isRowCreated) {
                    throw new Exception('Failed to revoke charges');
                }
            }
            foreach ($getMedsysCashAssessment_items as $item) {
                $isMedsysRowCreated = MedsysCashAssessment::create($this->medicineSuppliesData->MedsysCashAssessmentData($request, $item, $checkUser));
                if(!$isMedsysRowCreated) {
                    throw new Exception('Failed to revoke charges');
                }
            }
        }
    }