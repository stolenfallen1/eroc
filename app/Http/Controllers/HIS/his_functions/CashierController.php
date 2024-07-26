<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\HIS\BillingOutModel;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\CashORMaster;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    //
    public function populatechargeitem(Request $request)
    {
        DB::beginTransaction();
        try {
            $refNum = $request->query('refNum');

            $data = CashAssessment::with('items', 'doctor_details')
                ->where('refNum', $refNum)
                ->where('ORNumber', null)
                ->get();

            DB::commit();
            return response()->json(['data' => $data], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function save(Request $request)
    {
        DB::beginTransaction();
        try {
            $patient_id = $request->payload['patient_id'];
            $case_no = $request->payload['case_no'];
            $transDate = Carbon::now()->format('Y-m-d');
            $revenue_id = $request->payload['transaction_code'];
            $refNum = $request->payload['refNum'];
            $ORNum = $request->payload['ORNumber'];
            $tin = $request->payload['tin'];
            $business_style = $request->payload['business_style'];
            $osca_pwd_id = $request->payload['osca_pwd_id'];
            $shift = $request->payload['shift'];

            $update = CashAssessment::where('patient_id' , $patient_id)
                ->where('case_no', $case_no)
                ->where('revenueID', $revenue_id)
                ->update([
                    'ORNumber' => $ORNum, 
                    'updatedBy' => Auth()->user()->idnumber, 
                    'updated_at' => Carbon::now()
            ]);

            if (!$update) {
                throw new \Exception('Failed to update assessment');
            } else {
                if (isset($request->payload['Items']) && count($request->payload['Items']) > 0) {
                    foreach ($request->payload['Items'] as $item) {
                        $item_id = $item['itemID'];
                        $amount = floatval(str_replace([',', '₱'], '', ['amount']));
                        BillingOutModel::create([
                            'pid' => $patient_id,
                            'case_no' => 'CASH',
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
                            'HostName' => (new GetIP())->getHostname(),
                            'accountnum' => $patient_id,
                            'auto_discount' => 0,
                        ]);
                        CashORMaster::create([
                            'RefNum' => $ORNum,
                            'IDNum' => $case_no,
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
                            // 'BankCheck' => "TEST",
                            // 'Checknum' => "TEST",
                            // 'CheckAmount' => "TEST",
                            // 'CheckDate' => "TEST",
                            // 'CardName' => "TEST",
                            // 'CardAmount' => "TEST",
                            // 'CardApprovalNum' => "TEST",
                            // 'CardDate' => "TEST",
                            // 'PMO' => "TEST",
                            // 'PMOAmount' => "TEST",
                            // 'NetAmount' => "TEST",
                            // 'Vat' => "TEST",
                            // 'Discount' => "TEST",
                            // 'CashAmount' => "TEST",
                            // 'CashTendered' => "TEST",
                            'UserID' => Auth()->user()->idnumber,
                            'Status' => '', 
                            'Shift' => $shift,
                            'EncoderID' => Auth()->user()->idnumber,
                            'Hostname' => (new GetIP())->getHostname(),
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json(['message' => 'Successfully saved'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getOR(Request $request)
    {
        DB::beginTransaction();
        try {
            $ORNumber = $request->query('ORNumber');

            $data = CashAssessment::with('items', 'doctor_details')
                ->where('ORNumber', $ORNumber)
                ->get();

            DB::commit();
            return response()->json(['data' => $data], 200);

        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelOR(Request $request)
    {
        DB::beginTransaction();
        try {
            $items = $request->items;
            // foreach ($items as $item) {
            //     $ORNumber = $item['ORNumber'];
            //     $cancelDate = $item['CancelDate'];
            //     $cancelReason = $item['CancelledReason'];
            // }

            return response()->json(['data' => $items], 200);

            // $update = CashORMaster::where('RefNum', $ORNumber)
            //     ->update([
            //         'Status' => 'C',
            //         'CancelDate' => $cancelDate,
            //         'CancelledBy' => Auth()->user()->idnumber,
            //         'CancelledReason' => $cancelReason,
            // ]);

            // if (!$update) {
            //     throw new \Exception('Failed to cancel OR');
            // } else {
            //     $cashAssessment = CashAssessment::where('ORNumber', $ORNumber)->first();
            //     if (!$cashAssessment) {
            //         throw new \Exception('Cash Assessment not found');
            //     }
            //     $updateCashAssessment = CashAssessment::where('ORNumber', $ORNumber)
            //         ->update([
            //             'quantity' => -1,
            //             'amount' => - $cashAssessment->amount * -1,
            //             'recordStatus' => null,
            //             'dateRevoked' => $cancelDate,
            //             'revokedBy' => Auth()->user()->idnumber,
            //     ]);

            //     if (!$updateCashAssessment) {
            //         throw new \Exception('Failed to update Cash Assessment');
            //     } else {
            //         // $insertBillingOut = BillingOutModel::create([
            //         // ]);
            //     }
            //     DB::commit();
            //     return response()->json(['message' => 'OR successfully cancelled'], 200);
            // }

        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
