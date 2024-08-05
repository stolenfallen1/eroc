<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\HIS\BillingOutModel;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\CashORMaster;
use App\Models\HIS\his_functions\CashReceiptTerminal;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    //
    public function cashiersettings(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        try {
            $data = CashReceiptTerminal::updateOrCreate(
                [
                    'user_id' => $request->payload['user_id'],
                    'cashier_name' => $request->payload['cashier_name'],
                ],
                [
                    'branch_id' => 1,
                    'terminal_id' => (new GetIP())->getHostname(),
                    'user_id' => $request->payload['user_id'],
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
                $cash_receipt = CashReceiptTerminal::with('shift')->where('user_id', $request->payload['user_id'])->get();
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

            $data = CashAssessment::with('items', 'doctor_details')
                ->where('refNum', $refNum)
                ->where('ORNumber', null)
                ->get();

            return response()->json(['data' => $data], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function save(Request $request)  
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        try {
            $patient_id = $request->payload['patient_id'];
            $case_no = $request->payload['case_no'];
            $transDate = Carbon::now()->format('Y-m-d');
            $revenue_id = $request->payload['transaction_code'];
            $refNum = $request->payload['refNum'];
            $ORNum = $request->payload['ORNumber'];
            $tin = $request->payload['tin'] ?? '';
            $status = $request->payload['status'];
            $business_style = $request->payload['business_style'] ?? '';
            $osca_pwd_id = $request->payload['osca_pwd_id'] ?? '';
            $shift = $request->payload['Shift'];
            // CASH TRANSACTIONS
            $cash_amount = $request->payload['cash_amount'] ?? '';
            $cash_tendered = $request->payload['cash_tendered'] ?? '';
            $cash_change = $request->payload['cash_change'] ?? '';
            // CARD TRANSACTIONS
            $card_type_id = $request->payload['card_type_id'] ?? '';
            $card_id = $request->payload['card_id'] ?? '';
            $card_amount = $request->payload['card_amount'] ?? '';
            $card_approval_number = $request->payload['card_approval_number'] ?? '';
            $card_date = $request->payload['card_date'] ?? '';
            // CHECK TRANSACTIONS
            $bank_check = $request->payload['bank_check'] ?? '';
            $check_no = $request->payload['check_no'] ?? '';
            $check_amount = $request->payload['check_amount'] ?? '';
            $check_date = $request->payload['check_date'] ?? '';

            $update = CashAssessment::where('patient_id' , $patient_id)
                ->where('case_no', $case_no)
                ->where('revenueID', $revenue_id)
                ->update([
                    'ORNumber' => $ORNum, 
                    'recordStatus' => $status,
                    'updatedBy' => Auth()->user()->idnumber, 
                    'updated_at' => Carbon::now()
            ]);

            if (!$update) {
                DB::connection('sqlsrv_billingOut')->rollBack();
                throw new \Exception('Failed to update assessment');
            } else {
                if (isset($request->payload['Items']) && count($request->payload['Items']) > 0) {
                    foreach ($request->payload['Items'] as $item) {
                        $item_id = $item['itemID'];
                        $amount = floatval(str_replace([',', '₱'], '', $item['amount']));
                        BillingOutModel::create([
                            'pid' => $patient_id,
                            'case_no' => $case_no,
                            'transDate' => $transDate,
                            'msc_price_scheme_id' => 1,
                            'revenue_id' => $revenue_id,
                            'drcr' => 'D',
                            'item_id' => $item_id,
                            'quantity' => 1,
                            'refnum' => $ORNum,
                            'ChargeSlip' => $refNum,
                            'ornumber' => $ORNum,
                            'amount' => $amount,
                            'userid' => Auth()->user()->idnumber,
                            'net_amount' => $amount,
                            'record_status' => $status,
                            'HostName' => (new GetIP())->getHostname(),
                            'accountnum' => $patient_id,
                            'auto_discount' => 0,
                        ]);
                        CashORMaster::create([
                            'branch_id' => 1,
                            'RefNum' => $ORNum,
                            'case_no' => $case_no,
                            'HospNum' => $patient_id,
                            'TransDate' => $transDate,
                            'TIN' => $tin,
                            'BusinessStyle' => $business_style,
                            'SCPWDId' => $osca_pwd_id,
                            'Revenueid' => $revenue_id,
                            // 'PaymentType' => "TEST",
                            'PaymentFor' => $refNum,
                            // 'Particulars' => "TEST",
                            // 'PaymentFrom' => "TEST",
                            'BankCheck' => $bank_check,
                            'Checknum' => $check_no,
                            'CheckAmount' => $check_amount,
                            'CheckDate' => $check_date,
                            'card_type_id' => $card_type_id,
                            'card_id' => $card_id,
                            'CardAmount' => $card_amount,
                            'CardApprovalNum' => $card_approval_number,
                            'CardDate' => $card_date,
                            // 'PMO' => "TEST",
                            // 'PMOAmount' => "TEST",
                            // 'NetAmount' => "TEST",
                            // 'Vat' => "TEST",
                            // 'Discount' => "TEST",
                            'CashAmount' => $cash_amount,
                            'CashTendered' => $cash_tendered,
                            'ChangeAmount' => $cash_change,
                            'UserID' => Auth()->user()->idnumber,
                            'Status' => $status, 
                            'Shift' => $shift,
                            'Hostname' => (new GetIP())->getHostname(),
                        ]);
                    }
                }
            }
            DB::connection('sqlsrv_billingOut')->commit();
            return response()->json(['message' => 'Successfully saved'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getOR(Request $request)
    {
        try {
            $ORNumber = $request->query('ORNumber');

            $data = CashAssessment::with('items', 'doctor_details')
                ->where('ORNumber', $ORNumber)
                ->get();

            return response()->json(['data' => $data], 200);

        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelOR(Request $request)
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        try {
            $ORNumber = $request->items['ORNumber'];
            $cancelDate = $request->items['CancelDate'];
            $cancelReason = $request->items['CancelledReason'];
            $status = $request->items['status'];

            $update = CashORMaster::where('RefNum', $ORNumber)
                ->update([
                    'Status' => $status,
                    'CancelDate' => $cancelDate,
                    'CancelledBy' => Auth()->user()->idnumber,
                    'CancelledReason' => $cancelReason,
            ]);

            if (!$update) {
                DB::connection('sqlsrv_billingOut')->rollBack();
                throw new \Exception('Failed to cancel OR');
            } else {
                $cashAssessment = CashAssessment::where('ORNumber', $ORNumber)->first();
                if (!$cashAssessment) { 
                    throw new \Exception('Cash Assessment not found');
                } else {
                    $updateCashAssessment = CashAssessment::where('ORNumber', $ORNumber)
                        ->update([
                            'recordStatus' => '',
                            'ORNumber' => '',
                        ]);
                    if (!$updateCashAssessment) {
                        DB::connection('sqlsrv_billingOut')->rollBack();
                        throw new \Exception('Failed to update Cash Assessment');
                    } else {
                        $updateExistingBillingOut = BillingOutModel::where('ornumber', $ORNumber)
                            ->update([
                                'updatedBy' => Auth()->user()->idnumber,
                                'updated_at' => Carbon::now(),
                        ]);
                        if ($updateExistingBillingOut) {
                            $data = BillingOutModel::where('ornumber', $ORNumber)->get();
                            if ($data) {
                                foreach ($data as $record) {
                                    $newRecord = $record->replicate();
                                    $newRecord->transDate = Carbon::now();
                                    $newRecord->drcr = 'C';
                                    $newRecord->quantity = -1;
                                    $newRecord->amount = $record->amount * -1;
                                    $newRecord->net_amount = $record->net_amount * -1;
                                    $newRecord->record_status = $status;
                                    $newRecord->userId = Auth()->user()->idnumber;
                                    $newRecord->created_at = Carbon::now();
                                    $newRecord->createdby = Auth()->user()->idnumber;
                                    $newRecord->save();
                                }
                                DB::connection('sqlsrv_billingOut')->commit();
                                return response()->json(['message' => 'Successfully cancelled OR'], 200);
                            }
                        }
                    }
                }
            }

        } catch(\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
