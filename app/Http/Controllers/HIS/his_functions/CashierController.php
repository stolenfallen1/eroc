<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\Hospital\Company;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\CashierPaymentCode;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\CashORMaster;
use App\Models\HIS\his_functions\CashReceiptTerminal;
use App\Models\HIS\his_functions\ExamLaboratoryProfiles;
use App\Models\HIS\his_functions\LaboratoryMaster;
use App\Models\HIS\medsys\MedSysCashAssessment;
use App\Models\HIS\medsys\MedSysDailyOut;
use App\Models\HIS\medsys\tbCashORMaster;
use App\Models\HIS\medsys\tbLABMaster;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use App\Models\HIS\medsys\tbInvStockCard;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\MMIS\inventory\InventoryTransaction;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    protected $check_is_allow_medsys;

    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    //
    public function getLabItems($item_id) 
    {
        try {
            $data = ExamLaboratoryProfiles::with('lab_exams')
                ->where('map_profile_id', $item_id)
                ->get();
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function cashiersettings(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        try {
            $data = CashReceiptTerminal::updateOrCreate(
                [
                    'user_id' => Auth()->user()->idnumber,
                    'cashier_name' => $request->payload['cashier_name'],
                ],
                [
                    'branch_id' => 1,
                    'terminal_id' => (new GetIP())->getHostname(),
                    'user_id' => Auth()->user()->idnumber,
                    'cashier_name' => $request->payload['cashier_name'],
                    'ORSuffix' => strtoupper($request->payload['ORSuffix'] ?? ''),
                    'LastORnumber' => $request->payload['LastORnumber'],
                    'ORNumberFrom' => 0,
                    'ORNumberTo' => 99999,
                    'manualORNumber' => $request->payload['manualORNumber'] ?? '',
                    'manualOrNumberSuffix' => $request->payload['manualOrNumberSuffix'] ?? null,
                    'receiptType' => $request->payload['receiptType'] ?? null,
                    'shift_id' => $request->payload['shift_id'],
                    'collection_date' => $request->payload['collection_date'],
                    'createdby' => Auth()->user()->idnumber,
                    'updatedby' => Auth()->user()->idnumber,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
            if (!$data) {
                DB::connection('sqlsrv_billingOut')->rollBack();
                throw new \Exception('Failed to save settings');
            } else {
                DB::connection('sqlsrv_billingOut')->commit();
                $cash_receipt = CashReceiptTerminal::with('shift')->where('user_id', Auth()->user()->idnumber)->get();
                return response()->json(['data' => $cash_receipt], 200);
            }

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function populatechargeitem(Request $request)
    {
        try {
            $refNum = $request->query('refNum');
    
            $query = CashAssessment::query();
    
            if ($refNum) {
                $query->where('refNum', $refNum)
                    ->where('ORNumber', null)
                    ->whereIn('id', function($subQuery) use ($refNum) {
                        $subQuery->select(\DB::raw('MAX(id)'))
                            ->from('CashAssessment')
                            ->where('refNum', $refNum)
                            ->groupBy('refNum', 'itemID', 'case_No')
                            ->havingRaw('SUM(quantity) > 0')
                            ->havingRaw('SUM(amount) > 0');
                    });
            }
    
            $cashAssessments = $query->get();
            $revenueIDs = $cashAssessments->pluck('revenueID')->unique();
            $cashAssessments->load(['items' => function($itemQuery) use ($revenueIDs) {
                $itemQuery->whereIn('transaction_code', $revenueIDs); 
            }]);

            if (strpos($refNum, 'MD') === 0) {
                $cashAssessments->load('doctor_details');
            }
    
            return response()->json(['data' => $cashAssessments], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function populatePatientDataByCaseNo(Request $request) 
    {
        try {
            $case_No = $request->query('case_No');
            $patient_registry = PatientRegistry::where('case_No', $case_No)->firstOrFail();
            $patient_Id = $patient_registry->patient_Id;
            $data = PatientRegistry::with('patient_details')->where('patient_Id', $patient_Id)->get();

            return response()->json(['data' => $data], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function saveCashAssessment(Request $request)  
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_laboratory')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_medsys_laboratory')->beginTransaction();
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();

        try {
            $patient_Id             = $request->payload['patient_Id'];
            $case_No                = $request->payload['case_No'];
            $accountnum             = $request->payload['accountnum'];
            $request_doctors_id     = $request->payload['request_doctors_id'];
            $transDate              = Carbon::now();
            $refNum                 = $request->payload['refNum'];
            $Particulars            = $request->payload['particulars'];
            $ORNum                  = $request->payload['ORNumber'];
            $tin                    = $request->payload['tin'] ?? null;
            $business_style         = $request->payload['business_style'] ?? null;
            $osca_pwd_id            = $request->payload['osca_pwd_id'] ?? null;
            $Shift                  = $request->payload['Shift'];
            $PaymentType            = $request->payload['payment_code'];
            $PaymentFrom            = $request->payload['payors_name'];
            $discount_type          = $request->payload['discount'] ?? null;
            $discount               = $request->payload['discount_percent'] ?? null;
            // CASH TRANSACTIONS
            $cash_amount            = $request->payload['cash_amount'] ?? null;
            $cash_tendered          = $request->payload['cash_tendered'] ?? null;
            $cash_change            = $request->payload['cash_change'] ?? null;
            // CARD TRANSACTIONS
            $card_type_id           = $request->payload['card_type_id'] ?? null;
            $card_id                = $request->payload['card_id'] ?? null;
            $card_amount            = $request->payload['card_amount'] ?? null;
            $card_approval_number   = $request->payload['card_approval_number'] ?? null;
            $card_date              = $request->payload['card_date'] ?? null;
            // CHECK TRANSACTIONS
            $bank_check             = $request->payload['bank_check'] ?? null;
            $check_no               = $request->payload['check_no'] ?? null;
            $check_amount           = $request->payload['check_amount'] ?? null;
            $check_date             = $request->payload['check_date'] ?? null;
            $withholding_tax        = floatval(str_replace([',', '₱'], '', $request->payload['withholding_tax'] ?? 0));
            $total_payment          = floatval(str_replace([',', '₱'], '', $request->payload['total_payment']));

            if (isset($request->payload['Items']) && count($request->payload['Items']) > 0) {

                DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->increment('ChargeSlip');
                DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->increment('DispensingCSlip');

                $tbNursePHSlipSequence = DB::connection('sqlsrv_medsys_nurse_station')->table('tbNursePHSlip')->first();
                $tbInvChargeSlipSequence = DB::connection('sqlsrv_medsys_inventory')->table('tbInvChargeSlip')->first();

                $ORCashInsertOnce = true;

                foreach ($request->payload['Items'] as $item) {
                    $id = $item['id']; 
                    $itemID = $item['itemID'];
                    $item_amount = $item['amount'];
                    $form = $item['form'];
                    $revenueID = $item['revenueID'];
                    $specimen = $item['specimen'];
                    $charge_type = $item['charge_type'];
                    $barcode = $item['barcode'] ?? null;

                    $update = CashAssessment::where('patient_Id' , $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('refNum', $refNum)
                        ->where('revenueID', $revenueID)
                        ->where('itemID', $itemID)
                        ->where('id', $id)
                        ->update([
                            'ORNumber'          => $ORNum, 
                            'updatedBy'         => Auth()->user()->idnumber, 
                            'updated_at'        => Carbon::now()
                    ]);
                    
                    if ($update) {
                        $billingOut = HISBillingOut::create([
                            'patient_Id'            => $patient_Id,
                            'case_No'               => $case_No,
                            'accountnum'            => 'CASH',
                            'transDate'             => $transDate,
                            'msc_price_scheme_id'   => 1,
                            'revenueID'             => $revenueID,
                            'drcr'                  => 'D',
                            'itemID'                => $itemID,
                            'quantity'              => 1,
                            'refNum'                => $ORNum,
                            'ChargeSlip'            => $refNum,
                            'ornumber'              => $ORNum,
                            'withholdingTax'        => $withholding_tax,
                            'amount'                => $item_amount,
                            'request_doctors_id'    => $request_doctors_id,
                            'userid'                => Auth()->user()->idnumber,
                            'HostName'              => (new GetIP())->getHostname(),
                            'auto_discount'         => 0,
                            'created_at'            => Carbon::now(),
                            'createdby'             => Auth()->user()->idnumber,
                        ]);

                        if($this->check_is_allow_medsys && ($revenueID === 'EM' || $revenueID === 'RS')):

                            $isUpdatedRow = CashAssessment::where('refNum', $request->payload['reference_id'])->update([
                                'ORNumber'      => $ORNum,
                                'recordStatus'  => 'W',
                            ]);

                            $isMedysUpdatedRow = MedsysCashAssessment::where('RefNum', $request->payload['reference_id'])->update([
                                'ORNumber'      => $ORNum,
                                'RecordStatus'  => 'W',
                            ]);
            
                            if(!$isUpdatedRow || !$isMedysUpdatedRow):
                                throw new \Exception('Failed to paid charges');
                            else:
                                tbInvStockCard::create([
                                    'SummaryCode'   => $item['revenueID'],
                                    'HospNum'       => $patient_Id,
                                    'IdNum'         => $case_No . 'B',
                                    'ItemID'        => $itemID,
                                    'TransDate'     => Carbon::now(),
                                    'RevenueID'     => $revenueID ?? null,
                                    'RefNum'        => $tbInvChargeSlipSequence->DispensingCSlip,
                                    'Status'        => $item['stat'] ?? null,
                                    'Quantity'      => $request->payload['quantity'] ?? null,
                                    'Amount'        => $item_amount,
                                    'UserID'        => Auth()->user()->idnumber,
                                    'DosageID'      => $item['frequency'] ?? null,
                                    'DispenserCode' => 0,
                                    'RequestNum'    => $refNum,
                                    'RecordStatus'  => 'W',
                                    'ORNumber'      => $ORNum,
                                    'HostName'      => (new GetIP())->getHostname(),
                                ]);

                                tbNurseLogBook::create([
                                    'Hospnum'       => $patient_Id,
                                    'IDnum'         => $case_No . 'B' ?? null,
                                    'PatientType'   => $request->payload[''] ?? null,
                                    'RevenueID'     => $item['revenueID'] ?? null,
                                    'RequestDate'   => Carbon::now(),
                                    'ItemID'        => $itemID,
                                    'Description'   => $Particulars ?? null,
                                    'Quantity'      => $request->payload['quantity'] ?? null,
                                    'Dosage'        => $request->payload['frequency'] ?? null,
                                    'Amount'        => $item_amount,
                                    'RecordStatus'  => 'W',
                                    'UserID'        => Auth()->user()->idnumber,
                                    'ProcessBy'     => Auth()->user()->idnumber,
                                    'ProcessDate'   => Carbon::now(),
                                    'Remarks'       => $item['remarks'] ?? null,
                                    'RequestNum'    => $tbNursePHSlipSequence->ChargeSlip,
                                    'ReferenceNum'  => $tbInvChargeSlipSequence->DispensingCSlip,
                                    'Stat'          => $item['stat'] ?? null,
                                    'dcrdate'       => $request->payload['dcrdate'] ?? null,
                                    'isGeneric'     => 0,
                                    'AMPickup'      => 0,
                                ]);

                                NurseLogBook::create([
                                    'branch_Id'        => 1,
                                    'patient_Id'       => $patient_Id,
                                    'case_No'          => $case_No,
                                    'patient_Name'     => $PaymentFrom,
                                    'patient_Type'     => 0,
                                    'revenue_Id'       => $item['revenueID'],
                                    'requestNum'       => $tbNursePHSlipSequence->ChargeSlip,
                                    'referenceNum'     => $tbInvChargeSlipSequence->DispensingCSlip,
                                    'item_Id'          => $itemID,
                                    'description'      => $Particulars ?? null,
                                    'specimen_Id'      => $request->payload['specimen_Id'] ?? null,
                                    'Quantity'         => $request->payload['quantity'] ?? null,
                                    'dosage'           => $request->payload['frequency'] ?? null,
                                    'section_Id'       => $request->payload['section_Id'] ?? null,
                                    'amount'           => $item['amount'] ?? null,
                                    'user_Id'          => Auth()->user()->idnumber,
                                    'remarks'          => $item['remarks'] ?? null,
                                    'record_Status'    => 'W',
                                    'isGeneric'        => 0,
                                    'isMajorOperation' => 0,
                                    'createdat'        => Carbon::now(),
                                    'createdby'        => Auth()->user()->idnumber,
                                ]);

                                InventoryTransaction::create([
                                    'branch_Id'                     => 1,
                                    'warehouse_Group_Id'            => $request->payload['warehouse_Group_Id'] ?? null,
                                    'warehouse_Id'                  => $request->payload['warehouse_Id'] ?? null,
                                    'patient_Id'                    => $patient_Id,
                                    'patient_Registry_Id'           => $case_No,
                                    'transaction_Item_Id'           => $itemID,
                                    'transaction_Date'              => Carbon::now(),
                                    'trasanction_Reference_Number'  => $tbInvChargeSlipSequence->DispensingCSlip,
                                    'transaction_Acctg_TransType'   => $item['revenueID'] ?? null,
                                    'transaction_Qty'               => $request->payload['quantity'] ?? null,
                                    'transaction_Item_OnHand'       => $item['stockOnHand'] ?? null,
                                    'transaction_Item_ListCost'     => $request->payload['transaction_Item_ListCost'] ?? null,
                                    'transaction_Requesting_Number' => $tbNursePHSlipSequence->ChargeSlip,
                                    'transaction_UserId'            => Auth()->user()->idnumber,
                                    'created_at'                    => Carbon::now(),
                                    'createdBy'                     => Auth()->user()->idnumber,
                                    'updated_at'                    => Carbon::now(),
                                    'updatedby'                     => Auth()->user()->idnumber,
                                ]);
                            endif;

                        endif;

                        if ($this->check_is_allow_medsys):

                            MedSysCashAssessment::where('HospNum', $patient_Id)
                                ->where('IdNum', $case_No . 'B')
                                ->where('RefNum', $refNum)
                                ->where('RevenueID', $revenueID)
                                ->where('ItemID', $itemID)
                                ->update([
                                    'ORNumber'          => $ORNum,
                            ]);

                            MedSysDailyOut::create([
                                'HospNum'               => $patient_Id,
                                'IDNum'                 => 'CASH',
                                'TransDate'             => $transDate,
                                'RevenueID'             => $revenueID,
                                'DrCr'                  => 'D',
                                'ItemID'                => $itemID,
                                'Quantity'              => 1,
                                'RefNum'                => $ORNum,
                                'ChargeSlip'            => $refNum,
                                'Amount'                => $item_amount,
                                'DiscountType'          => $discount_type,
                                'withholdingtax'        => $withholding_tax, 
                                'AutoDiscount'          => 0,
                                'HostName'              => (new GetIP())->getHostname(),
                                'CashierID'             => Auth()->user()->idnumber,
                                'CashierShift'          => $Shift,
                            ]);

                        endif;

                        if ($ORCashInsertOnce) {
                            CashORMaster::create([
                                'branch_id'                 => 1,
                                'RefNum'                    => $ORNum,
                                'HospNum'                   => $patient_Id,
                                'case_no'                   => $case_No,
                                'TransDate'                 => $transDate,
                                'transaction_code'          => $revenueID, 
                                'TIN'                       => $tin,
                                'BusinessStyle'             => $business_style,
                                'SCPWDId'                   => $osca_pwd_id,
                                'Revenueid'                 => $revenueID, 
                                'PaymentType'               => $PaymentType,
                                'PaymentFor'                => $ORNum,
                                'Particulars'               => $Particulars,
                                'PaymentFrom'               => $PaymentFrom,
                                'Discount_type'             => $discount_type,
                                'Discount'                  => $discount,
                                'CashAmount'                => $cash_amount,
                                'CashTendered'              => $cash_tendered,
                                'ChangeAmount'              => $cash_change,
                                'card_type_id'              => $card_type_id,
                                'card_id'                   => $card_id,
                                'CardAmount'                => $card_amount,
                                'CardApprovalNum'           => $card_approval_number,
                                'CardDate'                  => $card_date,
                                'BankCheck'                 => $bank_check,
                                'Checknum'                  => $check_no,
                                'CheckAmount'               => $check_amount,
                                'CheckDate'                 => $check_date,
                                'UserID'                    => Auth()->user()->idnumber,
                                'Shift'                     => $Shift,
                                'Hostname'                  => (new GetIP())->getHostname(),
                                'createdby'                 => Auth()->user()->idnumber,
                                'created_at'                => Carbon::now(),
                            ]);
                            if ($this->check_is_allow_medsys):
                                tbCashORMaster::create([
                                    'HospNum'               => $patient_Id,
                                    'IDNum'                 => $case_No . 'B',
                                    'TransDate'             => $transDate,
                                    'PaymentFrom'           => $PaymentFrom,
                                    'PaymentType'           => $PaymentType,
                                    'PaymentFor'            => $ORNum,
                                    'Revenueid'             => $revenueID,
                                    'RefNum'                => $ORNum,
                                    'Particulars'           => $Particulars,
                                    'Amount'                => $total_payment,
                                    'UserID'                => Auth()->user()->idnumber,
                                    'Host_Name'             => (new GetIP())->getHostname(),
                                    'Shift'                 => $Shift,
                                    'Discount'              => $discount,
                                    'TIN'                   => $tin,
                                    'BusinessStyle'         => $business_style,
                                    'PWDID'                 => $osca_pwd_id,
                                    'CashAmount'            => $cash_amount,
                                    'CashTendered'          => $cash_tendered,
                                    'ChangeAmount'          => $cash_change,
                                    'Checknum'              => $check_no,
                                    'Bank'                  => $bank_check,
                                    'CheckAmount'           => $check_amount,
                                    'CheckDate'             => $check_date,
                                    'CardName'              => $card_id,
                                    'ApprovalNum'           => $card_approval_number,
                                    'CardAmount'            => $card_amount,
                                    'CardDate'              => $card_date,
                                ]);
                                
                            endif;
                            $ORCashInsertOnce = false;
                        }

                        if ($billingOut) {
                            if ($revenueID == 'LB' && $form == 'C') {
                                $labProfileData = $this->getLabItems($itemID);
                                if ($labProfileData->getStatusCode() === 200) {
                                    $labItems = $labProfileData->getData()->data;
                                    foreach ($labItems as $labItem) {
                                        foreach ($labItem->lab_exams as $exam) {
                                            LaboratoryMaster::create([
                                                'patient_Id'            => $patient_Id,
                                                'case_No'               => $case_No,
                                                'transdate'             => $transDate,
                                                'refNum'                => $refNum,
                                                'ornumber'              => $ORNum,
                                                'profileId'             => $exam->map_profile_id,
                                                'item_Charged'          => $exam->map_profile_id,
                                                'itemId'                => $exam->map_exam_id,
                                                'quantity'              => 1,
                                                'amount'                => 0,
                                                'doctor_Id'             => $request_doctors_id,
                                                'specimen_Id'           => $exam->map_specimen_id,
                                                'processed_By'          => Auth()->user()->idnumber,
                                                'processed_Date'        => $transDate,
                                                'isrush'                => $charge_type == 1 ? 'N' : 'Y',
                                                'request_Status'        => 'X', // Pending
                                                'result_Status'         => 'X', // Pending
                                                'userId'                => Auth()->user()->idnumber,
                                                'barcode'               => $barcode,
                                                'created_at'            => Carbon::now(),
                                                'createdby'             => Auth()->user()->idnumber,
                                            ]);
                                            if ($this->check_is_allow_medsys):
                                                tbLABMaster::create([
                                                    'HospNum'           => $patient_Id,
                                                    'IdNum'             => $case_No . 'B',
                                                    'RefNum'            => $refNum,
                                                    'RequestStatus'     => 'X',
                                                    'ItemId'            => $exam->map_exam_id,
                                                    'Amount'            => 0,
                                                    'Transdate'         => $transDate,
                                                    'DoctorId'          => $request_doctors_id,
                                                    'SpecimenId'        => $exam->map_specimen_id,
                                                    'UserId'            => Auth()->user()->idnumber,
                                                    'Quantity'          => 1,
                                                    'ResultStatus'      => 'X',
                                                    'RUSH'              => $charge_type == 1 ? 'N' : 'Y',
                                                    'ProfileId'         => $exam->map_profile_id,
                                                    'ItemCharged'       => $exam->map_profile_id,
                                                ]);
                                            endif;
                                        }
                                    }
                                }
                            } else if ($revenueID == 'LB') {
                                LaboratoryMaster::create([
                                    'patient_Id'            => $patient_Id,
                                    'case_No'               => $case_No,
                                    'transdate'             => $transDate,
                                    'refNum'                => $refNum,
                                    'ornumber'              => $ORNum,
                                    'profileId'             => $itemID,
                                    'item_Charged'          => $itemID,
                                    'itemId'                => $itemID,
                                    'quantity'              => 1,
                                    'amount'                => 0,
                                    'doctor_Id'             => $request_doctors_id,
                                    'specimen_Id'           => $specimen ?? 1, // BLOOD BY DEFAULT if no specimen
                                    'processed_By'          => Auth()->user()->idnumber,
                                    'processed_Date'        => $transDate,
                                    'isrush'                => $charge_type == 1 ? 'N' : 'Y',
                                    'request_Status'        => 'X', // Pending
                                    'result_Status'         => 'X', // Pending
                                    'userId'                => Auth()->user()->idnumber,
                                    'barcode'               => $barcode,
                                    'created_at'            => Carbon::now(),
                                    'createdby'             => Auth()->user()->idnumber,
                                ]);
                                if ($this->check_is_allow_medsys):
                                    tbLABMaster::create([
                                        'HospNum'           => $patient_Id,
                                        'IdNum'             => $case_No . 'B',
                                        'RefNum'            => $refNum,
                                        'RequestStatus'     => 'X',
                                        'ItemId'            => $itemID,
                                        'Amount'            => 0,
                                        'Transdate'         => $transDate,
                                        'DoctorId'          => $request_doctors_id,
                                        'SpecimenId'        => $specimen ?? 1,
                                        'UserId'            => Auth()->user()->idnumber,
                                        'Quantity'          => 1,
                                        'ResultStatus'      => 'X',
                                        'RUSH'              => $charge_type == 1 ? 'N' : 'Y',
                                        'ProfileId'         => $itemID,
                                        'ItemCharged'       => $itemID,
                                    ]);
                                endif;
                            }
                        }
                    }
                }

                DB::connection('sqlsrv_billingOut')->commit();
                DB::connection('sqlsrv_laboratory')->commit();
                DB::connection('sqlsrv_medsys_billing')->commit();
                DB::connection('sqlsrv_medsys_laboratory')->commit();
                DB::connection('sqlsrv_medsys_inventory')->commit();
                DB::connection('sqlsrv_mmis')->commit();
                DB::connection('sqlsrv_medsys_nurse_station')->commit();
                DB::connection('sqlsrv_patient_data')->commit();

                return response()->json(['message' => 'Successfully saved'], 200);
            }

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_laboratory')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();

            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine(), $e->getFile()], 500);
        }
    }
    public function saveOPDBill(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        try {
            $patient_Id             = $request->payload['patient_Id'];
            $case_No                = $request->payload['case_No'];
            $accountnum             = $request->payload['accountnum'];
            $transDate              = Carbon::now();
            $refNum                 = $request->payload['refNum'];
            $ORNum                  = $request->payload['ORNumber'];
            $tin                    = $request->payload['tin'] ?? null;
            $business_style         = $request->payload['business_style'] ?? null;
            $osca_pwd_id            = $request->payload['osca_pwd_id'] ?? null;
            $Shift                  = $request->payload['Shift'];
            $PaymentType            = $request->payload['payment_code'];
            $PaymentFrom            = $request->payload['payors_name'];
            $discount_type          = $request->payload['discount'] ?? null;
            $discount               = $request->payload['discount_percent'] ?? null;
            // CASH TRANSACTIONS
            $cash_amount            = $request->payload['cash_amount'] ?? null;
            $cash_tendered          = $request->payload['cash_tendered'] ?? null;
            $cash_change            = $request->payload['cash_change'] ?? null;
            // CARD TRANSACTIONS
            $card_type_id           = $request->payload['card_type_id'] ?? null;
            $card_id                = $request->payload['card_id'] ?? null;
            $card_amount            = $request->payload['card_amount'] ?? null;
            $card_approval_number   = $request->payload['card_approval_number'] ?? null;
            $card_date              = $request->payload['card_date'] ?? null;
            // CHECK TRANSACTIONS
            $bank_check             = $request->payload['bank_check'] ?? null;
            $check_no               = $request->payload['check_no'] ?? null;
            $check_amount           = $request->payload['check_amount'] ?? null;
            $check_date             = $request->payload['check_date'] ?? null;
            $withholding_tax        = floatval(str_replace([',', '₱'], '', $request->payload['withholding_tax'] ?? 0));
            $total_payment          = floatval(str_replace([',', '₱'], '', $request->payload['total_payment']));


            if (isset($request->payload['Items']) && count($request->payload['Items']) > 0) {
                foreach ($request->payload['Items'] as $item) {
                    $itemID = $item['itemID'];
                    $revenueID = $item['revenueID'];
                    $Particulars = isset($item['items']) ? $item['items']['exam_description'] : '';
                    
                    $billingOut = HISBillingOut::create([
                        'patient_Id'            => $patient_Id,
                        'case_No'               => $case_No,
                        'accountnum'            => $accountnum,
                        'transDate'             => $transDate,
                        'revenueID'             => $itemID, // PY
                        'drcr'                  => 'C',
                        'refNum'                => $ORNum,
                        'ChargeSlip'            => $ORNum,
                        'amount'                => $total_payment,
                        'discount_type'         => $discount_type,
                        'withholdingTax'        => $withholding_tax,
                        'auto_discount'         => 0,
                        'userId'                => Auth()->user()->idnumber,
                        'hostName'              => (new GetIP())->getHostname(),
                        'createdby'             => Auth()->user()->idnumber,
                        'created_at'            => Carbon::now(),
                    ]);
                    if ($billingOut) {
                        CashORMaster::create([
                            'branch_id'                 => 1,
                            'RefNum'                    => $ORNum,
                            'HospNum'                   => $patient_Id,
                            'case_no'                   => $case_No,
                            'TransDate'                 => $transDate,
                            'transaction_code'          => $itemID, // PY
                            'TIN'                       => $tin,
                            'BusinessStyle'             => $business_style,
                            'SCPWDId'                   => $osca_pwd_id,
                            'Revenueid'                 => $itemID, // PY
                            'PaymentType'               => $PaymentType,
                            'PaymentFor'                => $ORNum,
                            'Particulars'               => $Particulars,
                            'PaymentFrom'               => $PaymentFrom,
                            'Discount_type'             => $discount_type,
                            'Discount'                  => $discount,
                            'CashAmount'                => $cash_amount,
                            'CashTendered'              => $cash_tendered,
                            'ChangeAmount'              => $cash_change,
                            'card_type_id'              => $card_type_id,
                            'card_id'                   => $card_id,
                            'CardAmount'                => $card_amount,
                            'CardApprovalNum'           => $card_approval_number,
                            'CardDate'                  => $card_date,
                            'BankCheck'                 => $bank_check,
                            'Checknum'                  => $check_no,
                            'CheckAmount'               => $check_amount,
                            'CheckDate'                 => $check_date,
                            'UserID'                    => Auth()->user()->idnumber,
                            'Shift'                     => $Shift,
                            'Hostname'                  => (new GetIP())->getHostname(),
                            'createdby'                 => Auth()->user()->idnumber,
                            'created_at'                => Carbon::now(),
                        ]);
                        
                        if ($this->check_is_allow_medsys) {
                            MedSysDailyOut::create([
                                'HospNum'           => $patient_Id,
                                'IDNum'             => $case_No . 'B',
                                'TransDate'         => $transDate,
                                'RevenueID'         => $itemID, // PY
                                'DrCr'              => 'C',
                                'RefNum'            => $ORNum,
                                'ChargeSlip'        => $ORNum,
                                'Payment'           => $PaymentType,
                                'Amount'            => $total_payment,
                                'DiscountType'      => $discount_type,
                                'withholdingtax'    => $withholding_tax,
                                'AutoDiscount'      => 0,
                                'HostName'          => (new GetIP())->getHostname(),
                                'CashierID'         => Auth()->user()->idnumber,
                                'CashierShift'      => $Shift,
                            ]);

                            tbCashORMaster::create([
                                'HospNum'           => $patient_Id,
                                'IDNum'             => $case_No . 'B',
                                'PaymentFrom'       => $PaymentFrom,
                                'PaymentType'       => $PaymentType,
                                'PaymentFor'        => $ORNum,
                                'RefNum'            => $ORNum,
                                'Revenueid'         => $itemID, // PY
                                'Particulars'       => $Particulars,
                                'TransDate'         => $transDate,
                                'Amount'            => $total_payment,
                                'UserID'            => Auth()->user()->idnumber,
                                'Host_Name'         => (new GetIP())->getHostname(),
                                'Shift'             => $Shift,
                                'Discount'          => $discount,
                                'TIN'               => $tin,
                                'BusinessStyle'     => $business_style,
                                'PWDID'             => $osca_pwd_id,
                                'CashAmount'        => $cash_amount,
                                'CashTendered'      => $cash_tendered,
                                'ChangeAmount'      => $cash_change,
                                'Checknum'          => $check_no,
                                'Bank'              => $bank_check,
                                'CheckAmount'       => $check_amount,
                                'CheckDate'         => $check_date,
                                'CardName'          => $card_id,
                                'ApprovalNum'       => $card_approval_number,
                                'CardAmount'        => $card_amount,
                                'CardDate'          => $card_date,
                            ]);
                        }
            
                    }
                }
                DB::connection('sqlsrv_billingOut')->commit();
                DB::connection('sqlsrv_medsys_billing')->commit();
                return response()->json([
                    'message' => 'Successfully saved payment',
                ], 200);
            }

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function saveCompanyTransaction(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        
        try {
            $patient_Id             = $request->payload['patient_Id'];
            $case_No                = $request->payload['case_No'];
            $accountnum             = $request->payload['accountnum'];
            $transDate              = Carbon::now();
            $refNum                 = $request->payload['refNum'] ?? null;
            $ORNum                  = $request->payload['ORNumber'];
            $tin                    = $request->payload['tin'] ?? null;
            $business_style         = $request->payload['business_style'] ?? null;
            $osca_pwd_id            = $request->payload['osca_pwd_id'] ?? null;
            $Shift                  = $request->payload['Shift'];
            $PaymentType            = $request->payload['payment_code'];
            $PaymentFrom            = $request->payload['payors_name'];
            $discount_type          = $request->payload['discount'] ?? null;
            $discount               = $request->payload['discount_percent'] ?? null;
            // CASH TRANSACTIONS
            $cash_amount            = $request->payload['cash_amount'] ?? null;
            $cash_tendered          = $request->payload['cash_tendered'] ?? null;
            $cash_change            = $request->payload['cash_change'] ?? null;
            // CARD TRANSACTIONS
            $card_type_id           = $request->payload['card_type_id'] ?? null;
            $card_id                = $request->payload['card_id'] ?? null;
            $card_amount            = $request->payload['card_amount'] ?? null;
            $card_approval_number   = $request->payload['card_approval_number'] ?? null;
            $card_date              = $request->payload['card_date'] ?? null;
            // CHECK TRANSACTIONS
            $bank_check             = $request->payload['bank_check'] ?? null;
            $check_no               = $request->payload['check_no'] ?? null;
            $check_amount           = $request->payload['check_amount'] ?? null;
            $check_date             = $request->payload['check_date'] ?? null;
            $withholding_tax        = floatval(str_replace([',', '₱'], '', $request->payload['withholding_tax'] ?? 0));
            $total_payment          = floatval(str_replace([',', '₱'], '', $request->payload['total_payment']));


            if (isset($request->payload['Items']) && count($request->payload['Items']) > 0) {
                foreach ($request->payload['Items'] as $item) {
                    $itemID = $item['itemID'];
                    $revenueID = $item['revenueID'];
                    $Particulars = isset($item['items']) ? $item['items']['exam_description'] : '';
                    
                    $billingOut = HISBillingOut::create([
                        'patient_Id'            => $patient_Id,
                        'case_No'               => $case_No,
                        'accountnum'            => $accountnum,
                        'transDate'             => $transDate,
                        'revenueID'             => $itemID, // CP
                        'drcr'                  => 'C',
                        'refNum'                => $ORNum,
                        'ChargeSlip'            => $ORNum,
                        'amount'                => $total_payment,
                        'discount_type'         => $discount_type,
                        'withholdingTax'        => $withholding_tax,
                        'auto_discount'         => 0,
                        'userId'                => Auth()->user()->idnumber,
                        'hostName'              => (new GetIP())->getHostname(),
                        'createdby'             => Auth()->user()->idnumber,
                        'created_at'            => Carbon::now(),
                    ]);
                    if ($billingOut) {
                        CashORMaster::create([
                            'branch_id'                 => 1,
                            'RefNum'                    => $ORNum,
                            'HospNum'                   => $patient_Id,
                            'case_no'                   => $case_No,
                            'TransDate'                 => $transDate,
                            'transaction_code'          => $itemID, // CP
                            'TIN'                       => $tin,
                            'BusinessStyle'             => $business_style,
                            'SCPWDId'                   => $osca_pwd_id,
                            'Revenueid'                 => $itemID, // CP
                            'PaymentType'               => $PaymentType,
                            'PaymentFor'                => $ORNum,
                            'Particulars'               => $Particulars,
                            'PaymentFrom'               => $PaymentFrom,
                            'Discount_type'             => $discount_type,
                            'Discount'                  => $discount,
                            'CashAmount'                => $cash_amount,
                            'CashTendered'              => $cash_tendered,
                            'ChangeAmount'              => $cash_change,
                            'card_type_id'              => $card_type_id,
                            'card_id'                   => $card_id,
                            'CardAmount'                => $card_amount,
                            'CardApprovalNum'           => $card_approval_number,
                            'CardDate'                  => $card_date,
                            'BankCheck'                 => $bank_check,
                            'Checknum'                  => $check_no,
                            'CheckAmount'               => $check_amount,
                            'CheckDate'                 => $check_date,
                            'UserID'                    => Auth()->user()->idnumber,
                            'Shift'                     => $Shift,
                            'Hostname'                  => (new GetIP())->getHostname(),
                            'createdby'                 => Auth()->user()->idnumber,
                            'created_at'                => Carbon::now(),
                        ]);

                        if ($this->check_is_allow_medsys) {
                            MedSysDailyOut::create([
                                'HospNum'           => $patient_Id,
                                'IDNum'             => $case_No . 'B',
                                'TransDate'         => $transDate,
                                'RevenueID'         => $itemID, // CP
                                'DrCr'              => 'C',
                                'RefNum'            => $ORNum,
                                'ChargeSlip'        => $ORNum,
                                'Payment'           => $PaymentType,
                                'Amount'            => $total_payment,
                                'DiscountType'      => $discount_type,
                                'withholdingtax'    => $withholding_tax,
                                'AutoDiscount'      => 0,
                                'HostName'          => (new GetIP())->getHostname(),
                                'CashierID'         => Auth()->user()->idnumber,
                                'CashierShift'      => $Shift,
                            ]);
                            tbCashORMaster::create([
                                'HospNum'           => $patient_Id,
                                'IDNum'             => $case_No . 'B',
                                'PaymentFrom'       => $PaymentFrom,
                                'PaymentType'       => $PaymentType,
                                'PaymentFor'        => $ORNum,
                                'RefNum'            => $ORNum,
                                'Revenueid'         => $itemID, // CP
                                'Particulars'       => $Particulars,
                                'TransDate'         => $transDate,
                                'Amount'            => $total_payment,
                                'UserID'            => Auth()->user()->idnumber,
                                'Host_Name'         => (new GetIP())->getHostname(),
                                'Shift'             => $Shift,
                                'Discount'          => $discount,
                                'TIN'               => $tin,
                                'BusinessStyle'     => $business_style,
                                'PWDID'             => $osca_pwd_id,
                                'CashAmount'        => $cash_amount,
                                'CashTendered'      => $cash_tendered,
                                'ChangeAmount'      => $cash_change,
                                'Checknum'          => $check_no,
                                'Bank'              => $bank_check,
                                'CheckAmount'       => $check_amount,
                                'CheckDate'         => $check_date,
                                'CardName'          => $card_id,
                                'ApprovalNum'       => $card_approval_number,
                                'CardAmount'        => $card_amount,
                                'CardDate'          => $card_date,
                            ]);
                        }
                    }
                }

                DB::connection('sqlsrv_billingOut')->commit();
                DB::connection('sqlsrv_medsys_billing')->commit();
                return response()->json([
                    'message' => 'Successfully saved payment',
                ], 200);
            }

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            return response()->json([
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTrace(),
            ], 500);
        }
    }
    public function getORForCancellation(Request $request)
    {
        try {
            $ORNumber = $request->query('ORNumber');
            
            $query = CashAssessment::where('ORNumber', $ORNumber);
            $ORData = $query->get();
            $revenueIDs = $ORData->pluck('revenueID')->unique();
            if ($revenueIDs == 'MD') {
                $ORData->load('doctor_details');
            } else {
                $ORData->load(['items' => function($itemQuery) use ($revenueIDs) {
                    $itemQuery->whereIn('transaction_code', $revenueIDs);
                }]);
            }

            return response()->json(['data' => $ORData], 200);

        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function cancelOR(Request $request)
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction(); 
        DB::connection('sqlsrv_laboratory')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_medsys_laboratory')->beginTransaction();
        try {
            $patient_Id = $request->items['patient_Id'];
            $case_No = $request->items['case_No'];
            $refNum = $request->items['refNum'];
            $ORNumber = $request->items['ORNumber'];
            $cancelDate = $request->items['CancelDate'];
            $cancelReason = $request->items['CancelledReason'];
    
            CashORMaster::where('HospNum', $patient_Id)
                ->where('case_no', $case_No)
                ->where('RefNum', $ORNumber)
                ->updateOrFail([
                    'CancelDate' => $cancelDate,
                    'CancelledBy' => Auth()->user()->idnumber,
                    'CancelledReason' => $cancelReason,
            ]);
    
            $cashAssessment = CashAssessment::where('patient_Id', $patient_Id)
                ->where('case_No', $case_No)
                ->where('refNum', $refNum)
                ->where('ORNumber', $ORNumber)
                ->get();

            if ($cashAssessment->isEmpty()) throw new \Exception('Cash Assessment not found');
            foreach ($cashAssessment as $item) {
                $item->update([
                    'recordStatus' => null,
                    'ORNumber' => '',
                ]);
            }
            $existingBillingOut = HISBillingOut::where('patient_Id', $patient_Id)
                ->where('case_No', $case_No)
                ->where('ChargeSlip', $refNum)
                ->where('ornumber', $ORNumber)
                ->get();

            if ($existingBillingOut->isEmpty()) throw new \Exception('Billing Out not found');
            foreach ($existingBillingOut as $item) {
                $item->update([
                    'updatedBy' => Auth()->user()->idnumber,
                    'updated_at' => Carbon::now(),
                ]);
            }

            $replicatedItems = [];
            foreach ($existingBillingOut as $data) {
                $item = $data->replicate();
                $item->transDate = Carbon::now();
                $item->drcr = 'C';
                $item->quantity = $data->quantity * -1;
                $item->amount = $data->amount * -1;
                $item->userId = Auth()->user()->idnumber;
                $item->created_at = Carbon::now();
                $item->createdby = Auth()->user()->idnumber;

                if ($item->save()) {
                    $replicatedItems[] = $item;
                }
            }

            if (empty($replicatedItems)) throw new \Exception('No items replicated, submitted empty array');
            $labExams = LaboratoryMaster::where('patient_Id', $patient_Id)
                ->where('case_No', $case_No)
                ->where('refNum', $refNum)
                ->where('ornumber', $ORNumber)
                ->get();

            if ($labExams->isEmpty()) throw new \Exception('Lab Exams not found');
            foreach ($labExams as $exam) {
                $exam->update([
                    'request_Status' => 'R',
                    'result_Status' => 'R',
                ]);
            }

            if ($this->check_is_allow_medsys) {
                tbCashORMaster::where('HospNum', $patient_Id)
                    ->where('IDNum', $case_No . 'B')
                    ->where('RefNum', $ORNumber)
                    ->updateOrFail([
                        'CancelDate' => $cancelDate,
                        'CancelledBy' => Auth()->user()->idnumber,
                    ]);

                $medSysCashAssessment = MedSysCashAssessment::where('HospNum', $patient_Id)
                    ->where('IdNum', $case_No . 'B')
                    ->where('RefNum', $refNum)
                    ->where('ORNumber', $ORNumber)
                    ->get();
                
                if ($medSysCashAssessment->isEmpty()) throw new \Exception('Cash Assessment not found in MedSys DB');
                foreach ($medSysCashAssessment as $item) {
                    $item->update([
                        'RecordStatus' => null,
                        'ORNumber' => '',
                    ]);
                }

                $existingMedSysBillingOut = MedSysDailyOut::where('HospNum', $patient_Id)
                    ->where('IDNum', 'CASH')
                    ->where('ChargeSlip', $refNum)
                    ->where('RefNum', $ORNumber)
                    ->get();
                
                $medSysReplicatedItems = [];
                foreach ($existingMedSysBillingOut as $data) {
                    $item = $data->replicate();
                    $item->TransDate = Carbon::now();
                    $item->DrCr = 'C';
                    $item->Quantity = $data->quantity * -1;
                    $item->Amount = $data->amount * -1;
                    $item->UserID = Auth()->user()->idnumber;

                    if ($item->save()) {
                        $medSysReplicatedItems[] = $item;
                    }
                }

                if (empty($medSysReplicatedItems)) throw new \Exception('No items replicated in MedSys DB, submitted empty array');
                $medSysLabExams = tbLABMaster::where('HospNum', $patient_Id)
                    ->where('IdNum', $case_No . 'B')
                    ->where('RefNum', $refNum)
                    ->where('ORNum', $ORNumber)
                    ->get();

                if ($medSysLabExams->isEmpty()) throw new \Exception('Lab Exams not found in MedSys DB');
                foreach ($medSysLabExams as $exam) {
                    $exam->update([
                        'RequestStatus' => 'R',
                        'ResultStatus' => 'R',
                    ]);
                }
            }

            DB::connection('sqlsrv_billingOut')->commit(); 
            DB::connection('sqlsrv_laboratory')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            DB::connection('sqlsrv_medsys_laboratory')->commit();
            return response()->json(['message' => 'Successfully cancelled OR'], 200);
    
        } catch(\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack(); 
            DB::connection('sqlsrv_laboratory')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getpaymentcode() 
    {
        try {
            $data = CashierPaymentCode::get();
            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getCashierDiscount() 
    {
        try {
            $data = TransactionCodes::where('lgrp', 'D')
                ->where('isActive', 1)
                ->get();
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getORSequence(Request $request)
    {
        try {
            $userId = Auth()->user()->idnumber;
            $data = CashReceiptTerminal::where('user_id', $userId)->get();
    
            if ($data->isEmpty()) {
                return response()->json(['error' => 'User not found'], 200);
            }
    
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }    
    public function getOPDBill(Request $request) 
    {
        try {
            $case_No = $request->items['case_No'];
            $HospitalBill = $request->items['HospitalBill'];

            if ($HospitalBill == 'HB') {
                $data = DB::connection('sqlsrv_billingOut')
                    ->select("SET NOCOUNT ON; exec CDG_BILLING.[dbo].[sp_ComputeCashOPDHospitalBill] ?", [$case_No]);
    
                return response()->json(['data' => $data], 200);
            } else {
                throw new \Exception('Call IT Department');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getCompanyDetails(Request $request) 
    {
        try {
            $company_code = $request->query('company_code');
            $data = Company::where('guarantor_code', $company_code)->firstOrFail();
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
