<?php
    namespace App\Helpers\HIS\MedicineSuppliesCharges;
    use DB;

    class DatabaseTransactionController {
        private function handleStartTransaction() {
            DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
            DB::connection('sqlsrv_patient_data')->beginTransaction();
            DB::connection('sqlsrv_mmis')->beginTransaction();
            DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
            DB::connection('sqlsrv_billingOut')->beginTransaction();
            DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        }
        private function handleCommitTransaction() {
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_medsys_inventory')->commit();
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
        }
        private function handleRollbackTransaction() {
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
        }

        public function handleDatabaseTransactionProcess($progressStatus) {
            switch ($progressStatus) {
                case 'start':
                    $this->handleStartTransaction();
                    break;
                case 'commit':
                    $this->handleCommitTransaction();
                    break;
                case 'rollback':
                    $this->handleRollbackTransaction();
                    break;
                default:
                    throw new \InvalidArgumentException("Invalid progress status: $progressStatus");
            }
        }

        private function queryCashAssessmentTransaction($id) {
            $charges = DB::table('CDG_BILLING.dbo.CashAssessment as ca')
                ->select(
                    'ca.patient_Id',
                    'ca.case_No',
                    'ca.issupplies',
                    'ca.ismedicine',
                    'ca.assessnum',
                    'ca.revenueID',
                    'ca.refNum',
                    'ca.itemID',
                    'ca.quantity',
                    'ca.amount',
                    'ca.dosage',
                    'ca.recordStatus',
                    'ca.requestDescription',
                    'ca.ORNumber',
                    'mscD.description as frequency'
                )
                ->leftJoin('CDG_CORE.dbo.mscDosages as mscD', 'ca.dosage', '=', 'mscD.dosage_id')
                ->where('ca.case_No', '=', $id)
                ->where('ca.recordStatus', '!=', 'R')
                ->get();
            return $charges;
        }

        private function queryHMOTransaction($id) {
            $charges = DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
                ->select(
                    'cdgLB.patient_Id',
                    'cdgLB.case_No',
                    'cdgLB.issupplies',
                    'cdgLB.ismedicine',
                    'cdgLB.revenue_Id',
                    'cdgLB.requestNum',
                    'cdgLB.referenceNum',
                    'cdgLB.item_Id',
                    'cdgLB.description',
                    'cdgLB.Quantity',
                    'cdgLB.item_OnHand',
                    'cdgLB.price',
                    'cdgLB.dosage',
                    'cdgLB.amount',
                    'cdgLB.record_Status',
                    'mscD.description as frequency'
                )
                ->leftjoin('CDG_CORE.dbo.mscDosages as mscD', 'cdgLB.dosage', '=', 'mscD.dosage_id')
                ->where('cdgLB.case_No', '=', $id)
                ->get();
            return $charges;
        }
        public function queryItems($account, $id) { 
            switch ($account) {
                case 'cash_assessment':
                    return $this->queryCashAssessmentTransaction($id);
                case 'hmo':
                    return $this->queryHMOTransaction($id);
                default:
                    throw new \InvalidArgumentException("Invalid account type: $account");
            }

        }
        public function handleItemMapping($dataCharges) {
            return $dataCharges->map(function($item) {
                return [
                    'isSupplies'    => $item->issupplies,
                    'isMedicine'    => $item->ismedicine,
                    'revenue_Id'    => $item->revenue_Id ?? $item->revenueID,
                    'record_Status' => $item->record_Status ?? $item->recordStatus,
                    'item_Id'       => $item->item_Id ?? $item->itemID,
                    'description'   => (isset($item->requestDescription) 
                                    ?  $item->requestDescription 
                                    :  $item->description ?? null),
                    'price'         => $item->price ?? null,
                    'Quantity'      => $item->Quantity ?? $item->quantity,
                    'dosage'        => $item->dosage ?? $item->dosage,
                    'amount'        => $item->amount ?? $item->amount,
                    'referenceNum'  => $item->referenceNum ?? $item->refNum,
                    'assessnum'     => $item->assessnum ?? null,
                    'frequency'     => $item->frequency ?? null,
                    'ORN'           => $item->ORNumber  ?? null,
                ];
            });
        }
    }