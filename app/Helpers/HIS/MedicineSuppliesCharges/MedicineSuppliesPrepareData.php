<?php 

namespace App\Helpers\HIS\MedicineSuppliesCharges;
use \Carbon\Carbon;
use App\Helpers\GetIP;

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
            'RevenueID'     => $item['code'] ?? null,
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
            'departmentID'          => 'ER',
            'requestDescription'    => $item['item_name'],
            'ismedicine'            => $item['code'] === 'EM' ? 1 : 0,
            'issupplies'            => $item['code'] === 'RS' ? 1 : 0,
            'requestDoctorID'       => '',
            'requestDoctorName'     => '',
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

}