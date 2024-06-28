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
        try {
            $payload = $request->json()->all();
            $patient_id = $payload['patient_id'];
            $case_no = $payload['case_no'];
            $today = Carbon::now()->format('Y-m-d');

            $charges = HISBillingOut::with('items')
                ->where('pid', $patient_id)
                ->where('case_no', $case_no)
                ->whereDate('transDate', $today)
                ->get();

            $filteredCharges = $charges->filter(function ($charge) {
                return in_array($charge->revenue_id, ['HD', 'LB', 'LX']); 
            });

            return response()->json(['charges' => $filteredCharges], 200);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function professionalfeehistory(Request $request) {
        try {
            $payload = $request->json()->all();
            $patient_id = $payload['patient_id'];
            $case_no = $payload['case_no'];
            $today = Carbon::now()->format('Y-m-d');

            $doctorcharges = HISBillingOut::with('doctor_details')
                ->where('pid', $patient_id)
                ->where('case_no', $case_no)
                ->whereDate('transDate', $today)
                ->get();

            $filteredCharges = $doctorcharges->filter(function ($doctorcharges) {
                return in_array($doctorcharges->revenue_id, ['MD']);
            });

            return response()->json(['doctorcharges' => $filteredCharges], 200);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
                    $amount = floatval(str_replace([',', '₱'], '', $charge['price']));


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
                        'patient_type' => 0,
                    ]);
                }
            }

            if (isset($request->payload['DoctorCharges']) && count($request->payload['DoctorCharges']) > 0) {
                foreach ($request->payload['DoctorCharges'] as $doctorcharges) {
                    $revenue_id = $doctorcharges['transaction_code'];
                    $item_id = $doctorcharges['doctor_code'];
                    $quantity = 1;
                    $amount = floatval(str_replace([',', '₱'], '', $doctorcharges['amount']));

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
                        'patient_type' => 0,
                    ]);
                }
            }

            $chargeslip_sequence->update(['seq_no' => $chargeslip_sequence->seq_no + 1]);
            DB::commit();
            return response()->json(['message' => 'Charges posted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
