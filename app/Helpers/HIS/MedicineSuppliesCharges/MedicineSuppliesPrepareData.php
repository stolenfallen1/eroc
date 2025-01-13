<?php 

namespace App\Helpers\HIS\MedicineSuppliesCharges;
use \Carbon\Carbon;
use App\Helpers\GetIP;
use DB;

class MedicineSuppliesPrepareData {
    public function prepareMedsysLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID) {
        return [
            'Hospnum'       => $request->payload['patient_Id'] ?? null,
            'IDnum'         => $request->payload['case_No'] . 'B' ?? null,
            'PatientType'   => $request->payload[''] ?? null,
            'RevenueID'     => $item['code'],
            'RequestDate'   => Carbon::now(),
            'ItemID'        => $itemID,
            'Description'   => $item['item_name'] ?? null,
            'Quantity'      => $item['quantity'] ?? null,
            'Dosage'        => $item['frequency'] ?? null,
            'Amount'        => $item['amount'],
            'RecordStatus'  => 'W',
            'UserID'        => $checkUser->idnumber,
            'ProcessBy'     => $checkUser->idnumber,
            'ProcessDate'   => Carbon::now(),
            'Remarks'       => $item['remarks'] ?? null,
            'RequestNum'    => $tbNursePHSlipSequence,
            'ReferenceNum'  => $tbInvChargeSlipSequence,
            'Stat'          => $item['stat'] ?? null,
            'dcrdate'       => $request->payload['dcrdate'] ?? null,
            'isGeneric'     => 0,
            'AMPickup'      => 0,
        ];
    }

    public function prepareStockCardData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $listCost) {
        $item_price =  $item['price'] = str_replace('₱', '', $item['price']);
        return [
            'SummaryCode'   => $item['code'],
            'HospNum'       => $request->payload['patient_Id'] ?? null,
            'IdNum'         => $request->payload['case_No'] . 'B' ?? null,
            'ItemID'        => $item['map_item_id'] ?? null,
            'TransDate'     => Carbon::now(),
            'RevenueID'     => 'PH', //$item['code'] ?? null,
            'RefNum'        => $tbInvChargeSlipSequence,
            'Status'        => $item['stat'] ?? null,
            'Quantity'      => $item['quantity'] ?? null,
            'Balance'       => $request->payload['Balance'] ?? null,
            'NetCost'       => $item_price ? floatval($item_price) : null,
            'Amount'        => $item['amount'] ?? null,
            'UserID'        => $checkUser->idnumber,
            'DosageID'      => $item['frequency'] ?? null,
            'RequestByID'   => $checkUser->idnumber,
            'CreditMemoNum' => $request->payload['CreditMemoNum'] ?? null,
            'DispenserCode' => 0,
            'RequestNum'    => $tbNursePHSlipSequence,
            'ListCost'      => $listCost,
            'RecordStatus'  => 'W',
            'HostName'      => (new GetIP())->getHostname(),
        ];
    }

    public function prepareCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence) {
        return [
            'branch_id'             => 1,
            'patient_id'            => $request->payload['patient_Id'],
            'case_No'               => $request->payload['case_No'],
            'patient_Name'          => $request->payload['patient_Name'],
            'transdate'             => Carbon::now(),
            'assessNum'             => intval($medsysCashAssessmentSequence['RequestNum']),
            'Indicator'             => $item['code'],
            'drcr'                  => 'C',
            'stat'                  => 1,
            'revenueID'             => $item['code'],
            'refNum'                => $item['code'] . intval($medsysCashAssessmentSequence['AssessmentNum']),
            'itemID'                => $itemID,
            'quantity'              => $item['quantity'],
            'amount'                => $item['amount'],
            'specimenId'            => '',
            'dosage'                => $item['frequency'] ?? null,
            'requestDoctorID'       => null,
            'requestDoctorName'     => null,
            'departmentID'          => 'ER',
            'userId'                => $checkUser->idnumber,
            'requestDescription'    => $item['item_name'],
            'ismedicine'            => $item['code'] === 'EM' ? 1 : 0,
            'issupplies'            => $item['code'] === 'RS' ? 1 : 0,
            'hostname'              => (new GetIP())->getHostname(),
            'createdBy'             => $checkUser->idnumber,
            'created_at'            => Carbon::now(),
        ];
    }

    public function prepareMedsysCashAssessmentData($request, $item, $checkUser, $itemID, $medsysCashAssessmentSequence) {
        $item_price =  $item['price'] = str_replace('₱', '', $item['price']);
        return [
            'IdNum'         => $request->payload['case_No'] . 'B',
            'HospNum'       => $request->payload['patient_Id'],
            'Name'          => $request->payload['patient_Name'],
            'TransDate'     => Carbon::now() ?? null,
            'AssessNum'     => intval($medsysCashAssessmentSequence['RequestNum']),
            'Indicator'     => $item['code'],
            'DrCr'          => 'D',
            'RecordStatus'  => 'X',
            'ItemID'        => $itemID,
            'Quantity'      => $item['quantity'],
            'RefNum'        => $item['code'] . intval($medsysCashAssessmentSequence['AssessmentNum']),
            'Amount'        => $item['amount'],
            'UserID'        => $checkUser->idnumber,
            'RevenueID'     => $item['code'],
            'DepartmentID'  => 'ER',
            'UnitPrice'     => $item_price ? floatval($item_price) : null,
        ];
    }

    public function prepareNurseLogBookData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID) {
        return [
            'branch_Id'        => 1,
            'patient_Id'       => $request->payload['patient_Id'],
            'case_No'          => $request->payload['case_No'],
            'patient_Name'     => $request->payload['patient_Name'],
            'patient_Type'     => 0,
            'revenue_Id'       => $item['code'],
            'requestNum'       => $tbNursePHSlipSequence,
            'referenceNum'     => $tbInvChargeSlipSequence,
            'item_Id'          => $itemID,
            'description'      => $item['item_name'] ?? null,
            'specimen_Id'      => $request->payload['specimen_Id'] ?? null,
            'Quantity'         => $item['quantity'] ?? null,
            'dosage'           => $item['frequency'] ?? null,
            'section_Id'       => $request->payload['section_Id'] ?? null,
            'amount'           => $item['amount'] ?? null,
            'record_Status'    => 'W',
            'user_Id'          => $checkUser->idnumber,
            'remarks'          => $item['remarks'] ?? null,
            'ismedicine'       => $item['code'] === 'EM' ? 1 : 0,
            'issupplies'       => $item['code'] === 'RS' ? 1 : 0,
            'isGeneric'        => 0,
            'isMajorOperation' => 0,
            'createdat'        => Carbon::now(),
            'createdby'        => $checkUser->idnumber,
        ];
    }

    public function prepareInventoryTransactionData($request, $item, $checkUser, $tbNursePHSlipSequence, $tbInvChargeSlipSequence, $itemID, $stock) {
        return [
            'branch_Id'                     => 1,
            'warehouse_Group_Id'            => $request->payload['warehouse_Group_Id'] ?? null,
            'warehouse_Id'                  => $request->payload['warehouse_Id'] ?? null,
            'patient_Id'                    => $request->payload['patient_Id'] ?? null,
            'patient_Registry_Id'           => $request->payload['case_No'] ?? null,
            'transaction_Item_Id'           => $item['item_id'] ?? null,
            'transaction_Date'              => Carbon::now(),
            'trasanction_Reference_Number'  => $tbInvChargeSlipSequence,
            'transaction_Acctg_TransType'   => $item['code'] ?? null,
            'transaction_Qty'               => $item['quantity'] ?? null,
            'transaction_Item_OnHand'       => $stock,
            'transaction_Item_ListCost'     => $request->payload['transaction_Item_ListCost'] ?? null,
            'transaction_Requesting_Number' => $tbNursePHSlipSequence,
            'transaction_UserId'            => $checkUser->idnumber,
            'created_at'                    => Carbon::now(),
            'createdBy'                     => $checkUser->idnumber,
            'updated_at'                    => Carbon::now(),
            'updatedby'                     => $checkUser->idnumber,
        ];
    }

    public function CDGMMISInventoryData($item, $checkUser) {
        return [
            'branch_Id'                         => 1,
            'warehouse_Group_Id'                => $item->warehouse_Group_Id,
            'warehouse_Id'                      => $item->warehouse_Id,
            'patient_Id'                        => $item->patient_Id,
            'patient_Registry_Id'               => $item->patient_Registry_Id,    
            'transaction_Item_Id'               => $item->transaction_Item_Id,
            'transaction_Date'                  => Carbon::now(),
            'trasanction_Reference_Number'      => $item->trasanction_Reference_Number,
            'transaction_Acctg_TransType'       => $item->transaction_Acctg_TransType,
            'transaction_Qty'                   => (intval($item->transaction_Qty) * -1),
            'transaction_Item_OnHand'           => $item->transaction_Item_OnHand,
            'transaction_Item_ListCost'         => $item->transaction_Item_ListCost,
            'transaction_Requesting_Number'     => $item->transaction_Requesting_Number,
            'transaction_UserId'                => $checkUser->idnumber,
            'created_at'                        => Carbon::now(),
            'createdBy'                         => $checkUser->idnumber,
            'updated_at'                        => Carbon::now(),
            'updatedby'                         => $checkUser->idnumber,
        ];
    }

    public function MedsysStockCardData($request,  $item, $checkUser) {
        return [
            'SummaryCode'   => $item->SummaryCode,
            'HospNum'       => $request->payload['patient_Id'] ?? null,
            'IdNum'         => $request->payload['case_No'] . 'B' ?? null,
            'ItemID'        => $item->ItemID,
            'TransDate'     => Carbon::now(),
            'RevenueID'     => $item->RevenueID,
            'RefNum'        => $item->RefNum,
            'Status'        => $item->Status,
            'Quantity'      => intval($item->Quantity) * -1,
            'Balance'       => isset($item->Balance) 
                            ? $item->Balance 
                            : null,
            'NetCost'       => isset($item->NetCost) 
                            ? floatval($item->NetCost) 
                            : null,
            'Amount'        => floatval($item->Amount) * -1,
            'UserID'        => $checkUser->idnumber,
            'DosageID'      => $item->DosageID,
            'RequestByID'   => $checkUser->idnumber,
            'DispenserCode' => $item->DispenserCode,
            'RequestNum'    => $item->RequestNum,
            'ListCost'      => isset($item->ListCost) 
                            ? $item->ListCost 
                            : null,
            'RecordStatus'  => 'R',
            'HostName'      => (new GetIP())->getHostname(),
        ];
    }

    public function CDGCashAssessmentData($request, $item, $checkUser) {
        return [
            'branch_id'             =>  1,
            'patient_id'            =>  $request->payload['patient_Id'],
            'case_No'               =>  $request->payload['case_No'],
            'patient_Name'          =>  $request->payload['patient_Name'] ?? $request->payload['patient_name'],
            'transdate'             =>  Carbon::now(),
            'assessnum'             =>  $item->assessnum,
            'indicator'             =>  $item->indicator,
            'drcr'                  =>  'C',
            'stat'                  =>  1,
            'revenueID'             =>  $item->revenueID,
            'refNum'                =>  $item->refNum,
            'itemID'                =>  $item->itemID,
            'item_ListCost'         =>  $item->item_ListCost,
            'item_Selling_Amount'   =>  $item->item_Selling_Amount,
            'item_OnHand'           =>  $item->item_OnHand,
            'quantity'              =>  intval($item->quantity) * -1,
            'amount'                =>  floatval($item->amount) * -1,
            'specimenId'            =>  $item->specimenId,
            'dosage'                =>  $item->dosage,
            'recordStatus'          =>  'R',
            'requestDescription'    =>  $item->requestDescription,
            'departmentID'          =>  $item->departmentID,
            'userId'                =>  $checkUser->idnumber,
            'dateRevoked'           =>  Carbon::now(),
            'revokedBy'             =>  $checkUser->idnumber,
            'hostname'              =>  (new GetIP())->getHostname(),
            'updatedBy'             =>  $checkUser->idnumber,
            'updated_at'            =>  Carbon::now(),
        ];
    }

    public function MedsysCashAssessmentData($request, $item, $checkUser) {
        return [
            'IdNum'         =>  $request->payload['case_No'] . 'B',
            'HospNum'       =>  $request->payload['patient_Id'],
            'Name'          =>  $request->payload['patient_Name'] ?? $request->payload['patient_name'],
            'TransDate'     =>  Carbon::now() ?? null,
            'AssessNum'     =>  $item->AssessNum,
            'Indicator'     =>  $item->Indicator,
            'DrCr'          =>  'D',
            'ItemID'        =>  $item->ItemID,
            'RecordStatus'  =>  'R',
            'Quantity'      =>  intval($item->Quantity) * -1,
            'RefNum'        =>  $item->RefNum,
            'Amount'        =>  floatval($item->Amount) * -1,
            'UserID'        =>  $checkUser->idnumber,
            'RevenueID'     =>  $item->RevenueID,
            'UnitPrice'     =>  $item->UnitPrice ? floatval($item->UnitPrice) : null,
        ];
    }

    public function BillingOutData($request,  $billingOut, $checkUser) {
        return [
            'patient_Id'            => $request->payload['patient_Id'],
            'case_No'               => $request->payload['case_No'],
            'accountnum'            => $billingOut->accountnum,
            'msc_price_scheme_id'   => $billingOut->msc_price_scheme_id,
            'revenueID'             => $billingOut->revenueID,
            'drcr'                  => 'C',
            'itemID'                => $billingOut->itemID,
            'quantity'              => ($billingOut->quantity * -1),
            'refNum'                => $billingOut->refNum . '[REVOKED]',
            'chargeSlip'            => $billingOut->ChargeSlip . '[REVOKED]',
            'amount'                => ($billingOut->amount * -1),
            'net_amount'            => ($billingOut->net_amount * -1),
            'userId'                => $checkUser->idnumber,
            'hostName'              => (new GetIP())->getHostname(),
            'updatedBy'             => $checkUser->idnumber,
            'updated_at'            => Carbon::now(),
            'request_doctors_id'    => $billingOut->request_doctors_id,
            'transDate'             => Carbon::now(),

        ];
    }

    public function MedsysBillingOutData($request,  $medsys_billingOut, $checkUser) {
        return [
            'IDNum'         => $request->payload['case_No'] . 'B',
            'HospNum'       => $request->payload['patient_Id'],
            'TransDate'     => Carbon::now(),
            'RevenueID'     => $medsys_billingOut->RevenueID,
            'DrCr'          => 'C',
            'ItenID'        => $medsys_billingOut->itemID,
            'Quantity'      => ($medsys_billingOut->Quantity * -1),
            'RefNum'        => $medsys_billingOut->RefNum . '[REVOKED]',
            'Amount'        => ($medsys_billingOut->Amount * -1),
            'UserID'        => $checkUser->idnumber,
            'ChargeSlip'    => $medsys_billingOut->ChargeSlip . '[REVOKED]',
            'HostName'      => (new GetIP())->getHostname(),
        ];
    }
}
