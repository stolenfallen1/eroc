<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\his_functions\HISBillingOut;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HISPostChargesController extends Controller
{
    public function chargehistory(Request $request)
    {
        try {
            $patient_id = $request->patient_Id;
            $case_no = $request->case_No; 
            $code = $request->code;
            $data = $this->history($patient_id, $case_no, $code);
            return response()->json(['data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function history($patient_id, $case_no, $code, $refnum = [])
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $query = HISBillingOut::with('items','doctor_details')
                ->where('patient_Id', $patient_id)
                ->where('case_No', $case_no)
                ->where('quantity', '>', 0)
                ->whereDate('transDate', $today);

            if ($code == 'MD') {
                $query->whereIn('revenueID', ['MD']);
            } else if ($code == '') {
                $query->whereNotIn('revenueID', ['MD']);
            }
            if (count($refnum) > 0) {
                $query->whereIn('refNum', $refnum);
            }

            $query->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('BillingOut as cancelled')
                    ->whereColumn('cancelled.patient_Id', 'BillingOut.patient_Id')
                    ->whereColumn('cancelled.case_No', 'BillingOut.case_No')
                    ->whereColumn('cancelled.itemID', 'BillingOut.itemID')
                    ->whereColumn('cancelled.refNum', 'BillingOut.refNum')
                    ->where('cancelled.quantity', -1);
            });

            return $query->get();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function charge(Request $request)
    {
        DB::beginTransaction();
        try {
            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            
            if(!$checkUser):
                return response()->json([
                    'message' => 'Incorrect Username or Password',
                ], 404);
            endif;

            $chargeslip_sequence = SystemSequence::where('code', 'GCN')->first();
            if (!$chargeslip_sequence) {
                throw new \Exception('Chargeslip sequence not found');
            }

            $patient_id = $request->payload['patient_Id'];
            $case_no = $request->payload['case_No'];
            $transDate = Carbon::now();
            $msc_price_scheme_id = $request->payload['msc_price_scheme_id'];
            $request_doctors_id = $request->payload['attending_Doctor'];
            $guarantor_Id = $request->payload['guarantor_Id'];
            $refnum = [];
            if (isset($request->payload['Charges']) && count($request->payload['Charges']) > 0) {
                foreach ($request->payload['Charges'] as $charge) {
                    $revenue_id = $charge['code'];
                    $item_id = $charge['map_item_id'];
                    $quantity = $charge['quantity'];
                    $amount = floatval(str_replace([',', '₱'], '', $charge['price']));
                    $drcr = $charge['drcr'];
                    $lgrp = $charge['lgrp'];
                    $sequence = 'C' . $chargeslip_sequence->seq_no . 'L';
                    $refnum[] = $sequence;
                    HISBillingOut::create([
                        'patient_Id' => $patient_id,
                        'case_No' => $case_no,
                        'transDate' => $transDate,
                        'msc_price_scheme_id' => $msc_price_scheme_id,
                        'revenueID' => $revenue_id,
                        'drcr' => $drcr,
                        'lgrp' => $lgrp,
                        'itemID' => $item_id,
                        'quantity' => $quantity,
                        'refNum' => $sequence,
                        'ChargeSlip' => $sequence,
                        'amount' => $amount,
                        'userId' => $checkUser->idnumber,
                        'request_doctors_id' => $request_doctors_id,
                        'net_amount' => $amount,
                        'hostName' => (new GetIP())->getHostname(),
                        'accountnum' => $guarantor_Id,
                        'auto_discount' => 0,
                    ]);
                }
            }

            if (isset($request->payload['DoctorCharges']) && count($request->payload['DoctorCharges']) > 0) {
                foreach ($request->payload['DoctorCharges'] as $doctorcharges) {
                    $revenue_id = $doctorcharges['code'];
                    $item_id = $doctorcharges['doctor_code'];
                    $quantity = 1;
                    $amount = floatval(str_replace([',', '₱'], '', $doctorcharges['amount']));
                    $drcr = $doctorcharges['drcr'];
                    $lgrp = $doctorcharges['lgrp'];
                    $sequence = 'C' . $chargeslip_sequence->seq_no . 'L';
                    $refnum[] = $sequence;
                    HISBillingOut::create([
                        'patient_Id' => $patient_id,
                        'case_No' => $case_no,
                        'transDate' => $transDate,
                        'msc_price_scheme_id' => $msc_price_scheme_id,
                        'revenueID' => $revenue_id,
                        'drcr' => $drcr,
                        'lgrp' => $lgrp,
                        'itemID' => $item_id,
                        'quantity' => $quantity,
                        'refNum' => $sequence,
                        'ChargeSlip' => $sequence,
                        'amount' => $amount,
                        'userId' => $checkUser->idnumber,
                        'net_amount' => $amount,
                        'hostName' => (new GetIP())->getHostname(),
                        'accountnum' => $guarantor_Id,
                        'auto_discount' => 0,
                    ]);
                }
            }

            $chargeslip_sequence->update(['seq_no' => $chargeslip_sequence->seq_no + 1]);
            DB::commit();
            $data['charges'] =  $this->history($patient_id, $case_no, 'all', $refnum);
            return response()->json(['message' => 'Charges posted successfully', 'data' => $data], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function revokecharge(Request $request) 
    {
        DB::beginTransaction();
        try {
            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            
            if(!$checkUser):
                return response()->json([
                    'message' => 'Incorrect Username or Password',
                ], 404);
            endif;

            $items = $request->items;
            foreach ($items as $item) {
                $patient_id = $item['patient_Id'];
                $case_no = $item['case_No'];
                $refnum = $item['refNum'];
                $item_id = $item['itemID'];
                
                $existingData = HISBillingOut::where('patient_Id', $patient_id)
                    ->where('case_No', $case_no)
                    ->where('refNum', $refnum)
                    ->where('itemID', $item_id)
                    ->first();

                if ($existingData) {
                    HISBillingOut::create([
                        'patient_Id' => $existingData->patient_Id,
                        'case_No' => $existingData->case_No,
                        'transDate' => Carbon::now(),
                        'msc_price_scheme_id' => $existingData->msc_price_scheme_id,
                        'revenueID' => $existingData->revenueID,
                        'drcr' => 'C',
                        'itemID' => $existingData->itemID,
                        'quantity' => -1,
                        'refNum' => $existingData->refNum,
                        'amount' => $existingData->amount * -1,
                        'userId' => $checkUser->idnumber,
                        'net_amount' => $existingData->net_amount * -1,
                        'HostName' => (new GetIP())->getHostname(),
                        'accountnum' => $existingData->accountnum,
                        'auto_discount' => 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Charges revoked successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
