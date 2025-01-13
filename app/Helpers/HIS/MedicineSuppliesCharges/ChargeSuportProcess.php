<?php
    namespace App\Helpers\HIS\MedicineSuppliesCharges;

    use App\Models\HIS\his_functions\NurseLogBook;
    use App\Models\HIS\medsys\tbInvStockCard;
    use App\Models\HIS\medsys\tbNurseLogBook;
    use App\Models\MMIS\inventory\InventoryTransaction;
    use App\Models\HIS\MedsysCashAssessment;
    use App\Models\HIS\his_functions\CashAssessment;
    use Exception;
    use App\Helpers\HIS\MedicineSuppliesCharges\MedicineSuppliesUseSequences;
    use App\Helpers\HIS\MedicineSuppliesCharges\MedicineSuppliesPrepareData;

    class ChargeSuportProcess {
        protected $medicineSuppliesSequence;
        protected $medicineSuppliesData;

        public function __construct() {
            $this->medicineSuppliesSequence = new MedicineSuppliesUseSequences();
            $this->medicineSuppliesData = new MedicineSuppliesPrepareData();
        }
        public function processItems($request, $checkUser, $itemType) {
            $tbNursePHSlipSequence = $this->medicineSuppliesSequence->handleTbNursePHSlipSequence();
            $tbInvChargeSlipSequence = $this->medicineSuppliesSequence->handleTbInvChargeSlipSequence();
            $medsysCashAssessmentSequence = $this->medicineSuppliesSequence->handleMedsysCashAssessmentSequence();
            
            foreach ($request->payload[$itemType] as $index => $item) {
                if (!isset($item['code'], $item['item_name'], $item['quantity'], $item['amount'])) {
                    return response()->json(['message' => "Missing required $itemType information"], 400);
                }
                $itemsData = $this->loadItemsData($request, $itemType, $index);
                $itemID = $itemsData['itemID'];
                $listCost = $itemsData['listCost'];
                $stock = $itemsData['stock'];
                
                $this->processChargeItems($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $medsysCashAssessmentSequence, $itemID, $listCost, $stock);
            }
        }

        private function handleMedicineItems($request, $itemType, $index) {
            $itemID   = $request->payload['medicine_stocks_OnHand'][$index]['medicine_id'] ?? null;
            $listCost = $request->payload['medicine_stocks_OnHand'][$index]['item_List_Cost'] ?? null;
            $stock   = intval($request->payload['medicine_stocks_OnHand'][$index]['medicine_stock']) ?? null;
            return [
                'itemID' => $itemID,
                'listCost' => $listCost,
                'stock' => $stock
            ];
        }

        private function handleSupplyItems($request, $itemType, $index) {
            $itemID     = $request->payload['supply_stocks_OnHand'][$index]['supply_id'] ?? null;
            $listCost   = $request->payload['supply_stocks_OnHand'][$index]['item_List_Cost'] ?? null;
            $stock      = intval($request->payload['supply_stocks_OnHand'][$index]['supply_stock']) ?? null;
            return [
                'itemID' => $itemID,
                'listCost' => $listCost,
                'stock' => $stock
            ];
        }

        private function loadItemsData($request, $itemType, $index) {
            if($itemType === 'Medicines') {
                return $this->handleMedicineItems($request, $itemType, $index);
            } else if('Supplies') {
                return $this->handleSupplyItems($request, $itemType, $index);
            }
        }

        private function processTransactionNurseLogBook($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID) {
            $tb_medsys_nurse_logbook = tbNurseLogBook::create($this->medicineSuppliesData->prepareMedsysLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID));
            $cdg_nurse_logbook = NurseLogBook::create($this->medicineSuppliesData->prepareNurseLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID));
            if(!$tb_medsys_nurse_logbook || !$cdg_nurse_logbook) {
                throw new Exception('Failed to charge item');
            }
        }

        private function processTransactionInventory($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $stock, $listCost) {
            $CDG_MMIS_inventory_trasaction = InventoryTransaction::create($this->medicineSuppliesData->prepareInventoryTransactionData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $stock));
            $inventory_tb_stock_card = tbInvStockCard::create($this->medicineSuppliesData->prepareStockCardData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $listCost));
            if(!$CDG_MMIS_inventory_trasaction || !$inventory_tb_stock_card) {
                throw new Exception('Failed to charge item');
            }
        }

        private function processTransactionCashAssessment($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence) {
            $cdg_cash_assessment = CashAssessment::create($this->medicineSuppliesData->prepareCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence));
            $medsys_cash_assessment = MedsysCashAssessment::create($this->medicineSuppliesData->prepareMedsysCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence));
            if(!$cdg_cash_assessment || !$medsys_cash_assessment) {
                throw new Exception('Failed to charge item');
            }
        }

        private function processChargeItems($request , $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $medsysCashAssessmentSequence, $itemID, $listCost, $stock) {
            if($request->payload['charge_to'] === 'Company / Insurance') {
                $this->processTransactionNurseLogBook($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID);
                $this->processTransactionInventory($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $stock, $listCost);
            } else {
                $this->processTransactionCashAssessment($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence);
            }
        }

    }