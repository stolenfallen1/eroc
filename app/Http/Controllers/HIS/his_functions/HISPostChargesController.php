<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\his_functions\ExamLaboratoryProfiles;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\LaboratoryMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HISPostChargesController extends Controller
{
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
    public function getBarCode($barcode_prefix, $sequence, $specimenId) 
    {
        $barcode = $barcode_prefix . $sequence . $specimenId;
        $barcodeLength = strlen($barcode);
        switch ($barcodeLength) {
            case 4: 
                $barcode = 'XXXXXXXX' . $barcode;
                break;
            case 5:
                $barcode = 'XXXXXXX' . $barcode;
                break;
            case 6:
                $barcode = 'XXXXXX' . $barcode;
            case 7:
                $barcode = 'XXXXX' . $barcode;
                break;
            case 8:
                $barcode = 'XXXX' . $barcode;
                break;
            case 9: 
                $barcode = 'XXX' . $barcode;
            case 10: 
                $barcode = 'XX' . $barcode;
                break;
            case 11:
                $barcode = 'X' . $barcode;
                break;
        }
        return $barcode;
    }
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
            // $today = Carbon::now()->format('Y-m-d');
            $query = HISBillingOut::with('items','doctor_details')
                ->where('patient_Id', $patient_id)
                ->where('case_No', $case_no)
                ->where('quantity', '>', 0);
                // ->whereDate('transDate', $today);

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
                    $specimenId = $charge['specimen'];
                    $charge_type = $charge['charge_type'];
                    $sequence = 'C' . $chargeslip_sequence->seq_no . 'L';
                    $barcode_prefix = $charge['barcode_prefix'] ?? null;

                    if ($barcode_prefix === null) {
                        $barcode = '';
                    } else {
                        $barcode = $this->getBarCode($barcode_prefix, $sequence, $specimenId);
                    }

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
                        'userId' => Auth()->user()->idnumber,
                        'request_doctors_id' => $request_doctors_id,
                        'net_amount' => $amount,
                        'hostName' => (new GetIP())->getHostname(),
                        'accountnum' => $guarantor_Id,
                        'auto_discount' => 0,
                    ]);
                    if ($revenue_id == 'LB') {
                        $labProfileData = $this->getLabItems($item_id);
                        if ($labProfileData->getStatusCode() === 200) {
                            $labItems = $labProfileData->getData()->data;
                            foreach ($labItems as $labItem) {
                                foreach ($labItem->lab_exams as $exam) {
                                    LaboratoryMaster::create([
                                        'patient_Id'            => $patient_id,
                                        'case_No'               => $case_no,
                                        'transdate'             => $transDate,
                                        'refNum'                => $sequence,
                                        'profileId'             => $exam->map_profile_id,
                                        'item_Charged'          => $exam->map_profile_id,
                                        'itemId'                => $exam->map_exam_id,
                                        'quantity'              => 1,
                                        'amount'                => 0,
                                        'NetAmount'             => 0,
                                        'doctor_Id'             => $request_doctors_id,
                                        'specimen_Id'           => $exam->map_specimen_id,
                                        'isrush'                => $charge_type == 1 ? 'N' : 'Y',
                                        'userId'                => Auth()->user()->idnumber,
                                        'barcode'               => $barcode,
                                    ]);
                                }
                            }
                        }
                    }
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
                        'userId' => Auth()->user()->idnumber,
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
                        'userId' => Auth()->user()->idnumber,
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
