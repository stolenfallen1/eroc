<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\BuildFile\HISChargeSequence;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HISPostChargesController extends Controller
{
    public function chargehistory(Request $request)
    {
        try {
            $patient_id = $request->patient_id;
            $case_no = $request->case_no;
            $transaction_code = $request->transaction_code;
            $data = $this->history($patient_id, $case_no, $transaction_code);
            return response()->json(['data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function history($patient_id, $case_no, $transaction_code, $refnum = [])
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $query = HISBillingOut::with('items','doctor_details')
                ->where('pid', $patient_id)
                ->where('case_no', $case_no)
                ->where('quantity', '>', 0)
                ->whereDate('transDate', $today);

            if ($transaction_code == 'MD') {
                $query->whereIn('revenue_id', ['MD']);
            } else if ($transaction_code == '') {
                $query->whereNotIn('revenue_id', ['MD']);
            }
            if (count($refnum) > 0) {
                $query->whereIn('refnum', $refnum);
            }

            $query->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('BillingOut as cancelled')
                    ->whereColumn('cancelled.pid', 'BillingOut.pid')
                    ->whereColumn('cancelled.case_no', 'BillingOut.case_no')
                    ->whereColumn('cancelled.item_id', 'BillingOut.item_id')
                    ->whereColumn('cancelled.refnum', 'BillingOut.refnum')
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
            $chargeslip_sequence = HISChargeSequence::where('seq_prefix', 'gc')->first();
            if (!$chargeslip_sequence) {
                throw new \Exception('Chargeslip sequence not found');
            }

            $patient_id = $request->payload['patient_id'];
            $case_no = $request->payload['case_no'];
            $transDate = Carbon::now();
            $refnum = [];
            if (isset($request->payload['Charges']) && count($request->payload['Charges']) > 0) {
                foreach ($request->payload['Charges'] as $charge) {
                    $revenue_id = $charge['transaction_code'];
                    $item_id = $charge['map_item_id'];
                    $quantity = $charge['quantity'];
                    $amount = floatval(str_replace([',', '₱'], '', $charge['price']));
                    $sequence = $revenue_id . $chargeslip_sequence->seq_no;
                    $refnum[] = $sequence;
                    HISBillingOut::create([
                        'pid' => $patient_id,
                        'case_no' => $case_no,
                        'transDate' => $transDate,
                        'revenue_id' => $revenue_id,
                        'drcr' => 'D',
                        'item_id' => $item_id,
                        'quantity' => $quantity,
                        'refnum' => $sequence,
                        'amount' => $amount,
                        'userid' => Auth()->user()->idnumber,
                        'net_amount' => $amount,
                        'HostName' => (new GetIP())->getHostname(),
                        'accountnum' => $patient_id,
                        'auto_discount' => 0,
                    ]);
                }
            }

            if (isset($request->payload['DoctorCharges']) && count($request->payload['DoctorCharges']) > 0) {
                foreach ($request->payload['DoctorCharges'] as $doctorcharges) {
                    $revenue_id = $doctorcharges['transaction_code'];
                    $item_id = $doctorcharges['doctor_code'];
                    $quantity = 1;
                    $amount = floatval(str_replace([',', '₱'], '', $doctorcharges['amount']));
                    $sequence = $revenue_id . $chargeslip_sequence->seq_no;
                    $refnum[] = $sequence;
                    HISBillingOut::create([
                        'pid' => $patient_id,
                        'case_no' => $case_no,
                        'transDate' => $transDate,
                        'revenue_id' => $revenue_id,
                        'drcr' => 'D',
                        'item_id' => $item_id,
                        'quantity' => $quantity,
                        'refnum' => $sequence,
                        'amount' => $amount,
                        'userid' => Auth()->user()->idnumber,
                        'net_amount' => $amount,
                        'HostName' => (new GetIP())->getHostname(),
                        'accountnum' => $patient_id,
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
            $items = $request->items;
            foreach ($items as $item) {
                $patient_id = $item['pid'];
                $case_no = $item['case_no'];
                $refnum = $item['refnum'];
                $item_id = $item['item_id'];
                
                $existingData = HISBillingOut::where('pid', $patient_id)
                    ->where('case_no', $case_no)
                    ->where('refnum', $refnum)
                    ->where('item_id', $item_id)
                    ->first();

                if ($existingData) {
                    HISBillingOut::create([
                        'pid' => $existingData->pid,
                        'case_no' => $existingData->case_no,
                        'transDate' => Carbon::now(),
                        'revenue_id' => $existingData->revenue_id,
                        'drcr' => 'C',
                        'item_id' => $existingData->item_id,
                        'quantity' => -1,
                        'refnum' => $existingData->refnum,
                        'amount' => $existingData->amount * -1,
                        'userid' => Auth()->user()->idnumber,
                        'net_amount' => $existingData->net_amount * -1,
                        'HostName' => (new GetIP())->getHostname(),
                        'accountnum' => $existingData->pid,
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
