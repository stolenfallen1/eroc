<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\BuildFile\Hospital\Company;
use App\Models\HIS\BillingOutModel;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\CashierPaymentCode;
use App\Models\HIS\his_functions\CashORMaster;
use App\Models\HIS\his_functions\CashReceiptTerminal;
use App\Models\HIS\his_functions\ExamLaboratoryProfiles;
use App\Models\HIS\his_functions\LaboratoryMaster;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
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
                foreach ($request->payload['Items'] as $item) {
                    $id = $item['id']; 
                    $itemID = $item['itemID'];
                    $form = $item['form'];
                    $revenueID = $item['revenueID'];
                    $specimen = $item['specimen'];
                    $charge_type = $item['charge_type'];
                    $barcode = $item['barcode'] ?? null;

                    $update = CashAssessment::where('patient_Id' , $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('refNum', $refNum)
                        ->where('revenueID', $revenueID)
                        ->where('id', $id)
                        ->update([
                            'ORNumber'          => $ORNum, 
                            'updatedBy'         => Auth()->user()->idnumber, 
                            'updated_at'        => Carbon::now()
                    ]);
                    if (!$update) {
                        throw new \Exception('Failed to update Cash Assessment');
                    } else {
                        $billingOut = BillingOutModel::create([
                            'patient_Id'            => $patient_Id,
                            'case_No'               => $case_No,
                            'accountnum'            => $patient_Id,
                            'transDate'             => $transDate,
                            'msc_price_scheme_id'   => 1,
                            'revenueID'             => $revenueID,
                            'drcr'                  => 'D',
                            'itemID'                => $itemID,
                            'quantity'              => 1,
                            'refNum'                => $ORNum,
                            'ChargeSlip'            => $refNum,
                            'ornumber'              => $ORNum,
                            'withholdingTax'         => $withholding_tax,
                            'amount'                => $total_payment,
                            'net_amount'            => $total_payment,
                            'request_doctors_id'    => $request_doctors_id,
                            'userid'                => Auth()->user()->idnumber,
                            'HostName'              => (new GetIP())->getHostname(),
                            'auto_discount'         => 0,
                            'created_at'            => Carbon::now(),
                            'createdby'             => Auth()->user()->idnumber,
                        ]);
                        $cashORMaster = CashORMaster::create([
                            'branch_id'                 => 1,
                            'RefNum'                    => $ORNum,
                            'HospNum'                   => $patient_Id,
                            'case_no'                   => $case_No,
                            'TransDate'                 => $transDate,
                            // 'transaction_code'          => , 
                            'TIN'                       => $tin,
                            'BusinessStyle'             => $business_style,
                            'SCPWDId'                   => $osca_pwd_id,
                            // 'Revenueid'                 => , 
                            'PaymentType'               => $PaymentType,
                            'PaymentFor'                => $ORNum,
                            'Particulars'               => $Particulars,
                            'PaymentFrom'               => $PaymentFrom,
                            'Discount_type'             => $discount_type,
                            'Discount'                  => $discount,
                            'NetAmount'                 => $total_payment,
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

                        if (!$billingOut && !$cashORMaster) {
                            throw new \Exception('Failed to save in billing out and cash ORmaster');
                        } else {
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
                                                'NetAmount'             => 0,
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
                                    'NetAmount'             => 0,
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
                            }
                        }
                    }
                }
                DB::connection('sqlsrv_billingOut')->commit();
                return response()->json(['message' => 'Successfully saved'], 200);
            }

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveOPDBill(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
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
                    
                    $billingOut = BillingOutModel::create([
                        'patient_Id'            => $patient_Id,
                        'case_No'               => $case_No,
                        'accountnum'            => $accountnum,
                        'transDate'             => $transDate,
                        'revenueID'             => $itemID, // PY
                        'drcr'                  => 'C',
                        'refNum'                => $ORNum,
                        'ChargeSlip'            => $ORNum,
                        'amount'                => $total_payment,
                        'net_amount'            => $total_payment,
                        'discount_type'         => $discount_type,
                        'withholdingTax'        => $withholding_tax,
                        // 'discount'              => $discount,
                        'auto_discount'         => 0,
                        'userId'                => Auth()->user()->idnumber,
                        'hostName'              => (new GetIP())->getHostname(),
                        'createdby'             => Auth()->user()->idnumber,
                        'created_at'            => Carbon::now(),
                    ]);

                    if (!$billingOut) throw new \Exception('Failed to save billing out');
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
                        'NetAmount'                 => $total_payment,
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
                    DB::connection('sqlsrv_billingOut')->commit();
                    return response()->json([
                        'message' => 'Successfully saved payment',
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveCompanyTransaction(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
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
                    
                    $billingOut = BillingOutModel::create([
                        'patient_Id'            => $patient_Id,
                        'case_No'               => $case_No,
                        'accountnum'            => $accountnum,
                        'transDate'             => $transDate,
                        'revenueID'             => $itemID, // CP
                        'drcr'                  => 'C',
                        'refNum'                => $ORNum,
                        'ChargeSlip'            => $ORNum,
                        'amount'                => $total_payment,
                        'net_amount'            => $total_payment,
                        'discount_type'         => $discount_type,
                        'withholdingTax'        => $withholding_tax,
                        // 'discount'              => $discount,
                        'auto_discount'         => 0,
                        'userId'                => Auth()->user()->idnumber,
                        'hostName'              => (new GetIP())->getHostname(),
                        'createdby'             => Auth()->user()->idnumber,
                        'created_at'            => Carbon::now(),
                    ]);

                    if (!$billingOut) throw new \Exception('Failed to save billing out');
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
                        'NetAmount'                 => $total_payment,
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
                    DB::connection('sqlsrv_billingOut')->commit();
                    return response()->json([
                        'message' => 'Successfully saved payment',
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
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
        try {
            $patient_Id = $request->items['patient_Id'];
            $case_No = $request->items['case_No'];
            $refNum = $request->items['refNum'];
            $ORNumber = $request->items['ORNumber'];
            $cancelDate = $request->items['CancelDate'];
            $cancelReason = $request->items['CancelledReason'];
    
            $update = CashORMaster::where('HospNum', $patient_Id)
                ->where('case_no', $case_No)
                ->where('RefNum', $ORNumber)
                ->update([
                    'CancelDate' => $cancelDate,
                    'CancelledBy' => Auth()->user()->idnumber,
                    'CancelledReason' => $cancelReason,
            ]);
    
            if (!$update) {
                throw new \Exception('Failed to cancel OR');
            } else {
                $cashAssessment = CashAssessment::where('patient_Id', $patient_Id)
                    ->where('case_No', $case_No)
                    ->where('refNum', $refNum)
                    ->where('ORNumber', $ORNumber)
                    ->get();
    
                if ($cashAssessment->isEmpty()) throw new \Exception('Cash Assessment not found');
                foreach ($cashAssessment as $item) {
                    $item->update([
                        'recordStatus' => '',
                        'ORNumber' => '',
                    ]);
                }
    
                $existingBillingOut = BillingOutModel::where('patient_Id', $patient_Id)
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
                    $item->net_amount = $data->net_amount * -1;
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
                        'request_Status' => 'C',
                        'result_Status' => 'C',
                    ]);
                }
    
                DB::connection('sqlsrv_billingOut')->commit();
                return response()->json(['message' => 'Successfully cancelled OR'], 200);
            }
    
        } catch(\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
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
            $user = CashReceiptTerminal::where('user_id', '!=', null)->first();
            if ($user->user_id == Auth()->user()->idnumber) {
                $data = CashReceiptTerminal::where('user_id', Auth()->user()->idnumber)->get();
                return response()->json(['data' => $data], 200);
            } else {
                return response()->json(['error' => 'User not found'], 404);
            }
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
