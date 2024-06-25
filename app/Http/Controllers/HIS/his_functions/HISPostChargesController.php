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
    //
    public function chargehistory(Request $request) {
        // try {
        //     // Get all Charges in HISBillingOut base on patient_id
        //     $charges = HISBillingOut::query();
        //     $charges->where('pid', $request->patient_id);
        //     $charges->where('case_no', $request->case_no);
        // } catch (\Exception $e) {
        //     return response()->json(['error' => $e->getMessage()], 500);
        // }
    }

    public function charge(Request $request) {
        DB::beginTransaction();
        try {
            $chargeslip_sequence = HISChargeSequence::where('seq_prefix', 'gc')->first();

            if (!$chargeslip_sequence) {
                throw new \Exception('Chargeslip sequence not found');
            }

            $patient_id = $request->payload['patient_id'];
            $case_no = $request->payload['case_no'];
            $transDate = Carbon::now();
            $sequence = 'C' . $chargeslip_sequence->seq_no . 'K';

            if (isset($request->payload['Charges']) && count($request->payload['Charges']) > 0) {
                foreach ($request->payload['Charges'] as $charge) {
                    $revenue_id = $charge['transaction_code'];
                    $item_id = $charge['map_item_id'];
                    $quantity = $charge['quantity'];
                    $amount = floatval(str_replace('₱', '', $charge['price']));
                    $net_amount = floatval(str_replace('₱', '', $charge['price'])); 

                    HISBillingOut::create([
                        'pid' => $patient_id,
                        'case_no' => $case_no,
                        'transDate' => $transDate,
                        'revenue_id' => $revenue_id,
                        'drcr' => 'D', // To be confirmed
                        'item_id' => $item_id,
                        'quantity' => $quantity,
                        'refnum' => $sequence,
                        'amount' => $amount,
                        'userid' => Auth()->user()->idnumber,
                        // 'room_id' => $room_id,
                        'net_amount' => $net_amount,
                        'HostName' => (new GetIP())->getHostname(),
                        'accountnum' => $patient_id,
                        'auto_discount' => 0,
                        'patient_type' => 0,
                    ]);
                }
                $chargeslip_sequence->update(['seq_no' => $chargeslip_sequence->seq_no + 1]);
                DB::commit();
                return response()->json(['message' => 'Charges posted successfully']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
