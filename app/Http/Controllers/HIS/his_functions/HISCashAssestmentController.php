<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\BuildFile\HISChargeSequence;
use App\Models\HIS\his_functions\CashAssessment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HISCashAssestmentController extends Controller
{
    //
    public function getcashassessment(Request $request) 
    {
        try {
            $patient_id = $request->patient_id;
            $register_id_no = $request->case_no;
            $transaction_code = $request->transaction_code;
            $data = $this->history($patient_id, $register_id_no, $transaction_code);
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function history($patient_id, $register_id_no, $transaction_code, $refNum = [])
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $query = CashAssessment::with('items', 'doctor_details')
                ->where('patient_id', $patient_id)
                ->where('register_id_no', $register_id_no)
                ->where('quantity', '>', 0)
                ->whereDate('transdate', $today);

            if ($transaction_code == 'MD') {
                $query->whereIn('revenueID', ['MD']);
            } else if ($transaction_code == '') {
                $query->whereNotIn('revenueID', ['MD']);
            }
            if (count($refNum) > 0) {
                $query->whereIn('refNum', $refNum);
            }

            $query->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('CashAssessment as cancelled')
                    ->whereColumn('cancelled.patient_id', 'CashAssessment.patient_id')
                    ->whereColumn('cancelled.register_id_no', 'CashAssessment.register_id_no')
                    ->whereColumn('cancelled.itemID', 'CashAssessment.itemID')
                    ->whereColumn('cancelled.refNum', 'CashAssessment.refNum')
                    ->where('cancelled.quantity', -1);
            });

            return $query->get();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function cashassessment(Request $request) 
    {
        DB::beginTransaction();
        try {
            $chargeslip_sequence = HISChargeSequence::where('seq_prefix', 'gc')->first();
            if (!$chargeslip_sequence) {
                throw new \Exception('Charge Slip Sequence not found');
            }

            $patient_id = $request->payload['patient_id'];
            $register_id_no = $request->payload['case_no'];
            $patient_name = $request->payload['patient_name'];
            $transdate = Carbon::now();
            $refNum = [];
            if (isset($request->payload['Charges']) && count($request->payload['Charges']) > 0) {
                foreach ($request->payload['Charges'] as $charge) {
                    $revenueID = $charge['transaction_code'];
                    $itemID = $charge['map_item_id'];
                    $quantity = $charge['quantity'];
                    $amount = floatval(str_replace([',', '₱'], '', $charge['price']));
                    $sequence = $revenueID . $chargeslip_sequence->seq_no;
                    $refNum[] = $sequence;
                    CashAssessment::create([
                        'patient_id' => $patient_id,
                        'register_id_no' => $register_id_no,
                        'patient_name' => $patient_name,
                        'transdate' => $transdate,
                        'drcr' => 'C',
                        'revenueID' => $revenueID,
                        'itemID' => $itemID,
                        'quantity' => $quantity,
                        'refNum' => $sequence,
                        'amount' => $amount,
                        'userId' => Auth()->user()->idnumber,
                        'hostname' => (new GetIP())->getHostname(),
                        'createdBy' => Auth()->user()->idnumber,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

            if (isset($request->payload['DoctorCharges']) && count($request->payload['DoctorCharges']) > 0) {
                foreach ($request->payload['DoctorCharges'] as $doctorcharges) {
                    $revenueID = $doctorcharges['transaction_code'];
                    $itemID = $doctorcharges['doctor_code'];
                    $quantity = 1;
                    $amount = floatval(str_replace([',', '₱'], '', $doctorcharges['amount']));
                    $sequence = $revenueID . $chargeslip_sequence->seq_no;
                    $refNum[] = $sequence;
                    CashAssessment::create([
                        'patient_id' => $patient_id,
                        'register_id_no' => $register_id_no,
                        'patient_name' => $patient_name,
                        'transdate' => $transdate,
                        'drcr' => 'C',
                        'revenueID' => $revenueID,
                        'itemID' => $itemID,
                        'quantity' => $quantity,
                        'refNum' => $sequence,
                        'amount' => $amount,
                        'userId' => Auth()->user()->idnumber,
                        'hostname' => (new GetIP())->getHostname(),
                        'createdBy' => Auth()->user()->idnumber,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

            $chargeslip_sequence->update(['seq_no' => $chargeslip_sequence->seq_no + 1]);
            DB::commit();
            $data['charges'] = $this->history($patient_id, $register_id_no, 'all', $refNum);
            return response()->json(['message' => 'Charges posted successfully', 'data' => $data], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function revokecashassessment(Request $request) 
    {
        DB::beginTransaction();
        try {
            $items = $request->items;
            foreach ($items as $item) {
                $patient_id = $item['patient_id'];
                $register_id_no = $item['register_id_no'];
                $refNum = $item['refNum'];
                $itemID = $item['itemID'];

                $existingData = CashAssessment::where('patient_id', $patient_id)
                    ->where('register_id_no', $register_id_no)
                    ->where('refNum', $refNum)
                    ->where('itemID', $itemID)
                    ->first();

                if ($existingData) {
                    CashAssessment::create([
                        'patient_id' => $existingData->patient_id,
                        'register_id_no' => $existingData->register_id_no,
                        'patient_name' => $existingData->patient_name,
                        'transdate' => Carbon::now(),
                        'drcr' => 'C',
                        'revenueID' => $existingData->revenueID,
                        'itemID' => $existingData->itemID,
                        'quantity' => -1,
                        'refNum' => $existingData->refNum,
                        'amount' => $existingData->amount * -1,
                        'userId' => Auth()->user()->idnumber,
                        'hostname' => (new GetIP())->getHostname(),
                        'createdBy' => Auth()->user()->idnumber,
                        'created_at' => Carbon::now(),
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
