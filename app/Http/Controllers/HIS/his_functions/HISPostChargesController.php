<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\his_functions\ExamLaboratoryProfiles;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\LaboratoryMaster;
use App\Models\HIS\medsys\MedSysDailyOut;
use App\Models\HIS\medsys\tbLABMaster;
use App\Models\HIS\MedsysCashAssessment;
use Auth;
use Carbon\Carbon;
use App\Models\User;
use GlobalChargingSequences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HISPostChargesController extends Controller
{
    protected $check_is_allow_medsys;

    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
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
            $query = HISBillingOut::with('items','doctor_details')
                ->where('patient_Id', $patient_id)
                ->where('case_No', $case_no)
                ->where('quantity', '>', 0);

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
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_laboratory')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_medsys_laboratory')->beginTransaction();
        try {
            $checkUser = null;
            if (isset($request->payload['user_userid']) && isset($request->payload['user_passcode'])) {
                $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
                if (!$checkUser) {
                    return response()->json([
                        'message' => 'Incorrect Username or Password',
                    ], 404);
                }
            }

            if ($this->check_is_allow_medsys) {
                $chargeSlipSequences = new GlobalChargingSequences();
            } else {
                $chargeslip_sequence = SystemSequence::where('code', 'GCN')->first();
                if ($chargeslip_sequence) {
                    $chargeslip_sequence->increment('seq_no');
                    $chargeslip_sequence->increment('recent_generated');
                } else {
                    throw new \Exception('Sequence not found');
                }
            }

            $revenueCodeSequences = [
                'LB'    => 'MedSysLabSequence',
                'XR'    => 'MedSysXrayUltraSoundSequence',
                'US'    => 'MedSysXrayUltraSoundSequence',
                'CT'    => 'MedSysCTScanSequence',
                'MI'    => 'MedSysMRISequence',
                'MM'    => 'MedSysMammoSequence',
                'WC'    => 'MedSysCentreForWomenSequence',
                'NU'    => 'MedSysNuclearMedSequence',
                'ER'    => 'MedSysCashSequence'
            ];

            $sequenceGenerated = [];
            $xr_us_codes = ['XR', 'US'];
            $xr_us_incremented = false;
            $patient_id = $request->payload['patient_Id'];
            $case_no = $request->payload['case_No'];
            $transDate = Carbon::now();
            $msc_price_scheme_id = $request->payload['msc_price_scheme_id'];
            $request_doctors_id = $request->payload['attending_Doctor'];
            $guarantor_Id = $request->payload['guarantor_Id'];
            $refnum = [];

            if (isset($request->payload['Charges']) && count($request->payload['Charges']) > 0) {
                foreach ($request->payload['Charges'] as $charge) {
                    $revenueID = $charge['code'];
                    $item_id = $charge['map_item_id'];
                    $quantity = $charge['quantity'];
                    $amount = floatval(str_replace([',', '₱'], '', $charge['price']));
                    $drcr = $charge['drcr'];
                    $lgrp = $charge['lgrp'];
                    $form = $charge['form'] ?? null;
                    $specimenId = $charge['specimen'];
                    $charge_type = $charge['charge_type'];
                    $barcode_prefix = $charge['barcode_prefix'] ?? null;

                    if (in_array($revenueID, $xr_us_codes)) {
                        if (!$xr_us_incremented) {
                            $chargeSlipSequences->incrementSequence('XR'); // Increment XR and US sequence once only for both
                            $chargeslip_sequence = $chargeSlipSequences->getSequence();
                            $xr_us_incremented = true;
                        }
                    } else {
                        if (!isset($sequenceGenerated[$revenueID])) {
                            $chargeSlipSequences->incrementSequence($revenueID);
                            $chargeslip_sequence = $chargeSlipSequences->getSequence();
                            $sequenceGenerated[$revenueID] = true;
                        }
                    }

                    if (array_key_exists($revenueID, $revenueCodeSequences)) {
                        $sequenceType = $revenueCodeSequences[$revenueID];
                        $sequence = $revenueID . ($this->check_is_allow_medsys && isset($chargeslip_sequence[$sequenceType]) 
                            ? $chargeslip_sequence[$sequenceType] 
                            : $chargeslip_sequence['seq_no']);

                        $assessNum = ($this->check_is_allow_medsys && isset($chargeslip_sequence[$sequenceType])
                            ? $chargeslip_sequence[$sequenceType]
                            : $chargeslip_sequence['seq_no']);
                    }

                    if ($barcode_prefix === null) {
                        $barcode = '';
                    } else {
                        $barcode = $this->getBarCode($barcode_prefix, $sequence, $specimenId);
                    }
                    $refnum[] = $sequence;

                    $saveCharges = HISBillingOut::create([
                        'patient_Id' => $patient_id,
                        'case_No' => $case_no,
                        'transDate' => $transDate,
                        'msc_price_scheme_id' => $msc_price_scheme_id,
                        'revenueID' => $revenueID,
                        'drcr' => $drcr,
                        'lgrp' => $lgrp,
                        'itemID' => $item_id,
                        'quantity' => $quantity,
                        'refNum' => $sequence,
                        'ChargeSlip' => $sequence,
                        'amount' => $amount,
                        'userId' => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'request_doctors_id' => $request_doctors_id,
                        'net_amount' => $amount,
                        'hostName' => (new GetIP())->getHostname(),
                        'accountnum' => $guarantor_Id,
                        'auto_discount' => 0,
                    ]);
                    if ($saveCharges && $this->check_is_allow_medsys) {
                        MedSysDailyOut::create([
                            'HospNum'           => $patient_id,
                            'IDNum'             => $case_no . 'B',
                            'TransDate'         => $transDate,
                            'RevenueID'         => $revenueID,
                            'ItemID'            => $item_id,
                            'DrCr'              => $drcr,
                            'Quantity'          => $quantity,
                            'RefNum'            => $sequence,
                            'ChargeSlip'        => $sequence,
                            'Amount'            => $amount,
                            'UserID'            => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'HostName'          => (new GetIP())->getHostname(),
                            'AutoDiscount'      => 0,
                        ]);
                    }
                    if ($revenueID == 'LB' && $form == 'C') {
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
                                        'processed_By'          => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                        'processed_Date'        => $transDate,
                                        'isrush'                => $charge_type == 1 ? 'N' : 'Y',
                                        'request_Status'        => 'X',
                                        'result_Status'         => 'X',
                                        'userId'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                        'barcode'               => $barcode,
                                        'created_at'            => Carbon::now(),
                                        'createdby'             => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                    ]);
                                    if ($this->check_is_allow_medsys) {
                                        tbLABMaster::create([
                                            'HospNum'           => $patient_id,
                                            'IdNum'             => $case_no . 'B',
                                            'RefNum'            => $sequence,
                                            'RequestStatus'     => 'X',
                                            'ItemId'            => $exam->map_exam_id,
                                            'Amount'            => 0,
                                            'Transdate'         => $transDate,
                                            'DoctorId'          => $request_doctors_id,
                                            'SpecimenId'        => $exam->map_specimen_id,
                                            'UserId'            => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                            'Quantity'          => 1,
                                            'ResultStatus'      => 'X',
                                            'RUSH'              => $charge_type == 1 ? 'N' : 'Y',
                                            'ProfileId'         => $exam->map_profile_id,
                                            'ItemCharged'       => $exam->map_profile_id,
                                        ]);
                                    }
                                }
                            }
                        }
                    } else if ($revenueID == 'LB') {
                        LaboratoryMaster::create([
                            'patient_Id'            => $patient_id,
                            'case_No'               => $case_no,
                            'transdate'             => $transDate,
                            'refNum'                => $sequence,
                            'profileId'             => $item_id,
                            'item_Charged'          => $item_id,
                            'itemId'                => $item_id,
                            'quantity'              => 1,
                            'amount'                => 0,
                            'NetAmount'             => 0,
                            'doctor_Id'             => $request_doctors_id,
                            'specimen_Id'           => $specimenId ?? 1,
                            'processed_By'          => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'processed_Date'        => $transDate,
                            'isrush'                => $charge_type == 1 ? 'N' : 'Y',
                            'request_Status'        => 'X',
                            'result_Status'         => 'X',
                            'userId'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'barcode'               => $barcode,
                            'created_at'            => Carbon::now(),
                            'createdby'             => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys) {
                            tbLABMaster::create([
                                'HospNum'           => $patient_id,
                                // 'RequestNum'        => $sequence,
                                'IdNum'             => $case_no . 'B',
                                'RefNum'            => $sequence,
                                'RequestStatus'     => 'X',
                                'ItemId'            => $item_id,
                                'Amount'            => 0,
                                'Transdate'         => $transDate,
                                'DoctorId'          => $request_doctors_id,
                                'SpecimenId'        => $specimenId ?? 1,
                                'UserId'            => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'Quantity'          => 1,
                                'ResultStatus'      => 'X',
                                'RUSH'              => $charge_type == 1 ? 'N' : 'Y',
                                'ProfileId'         => $item_id,
                                'ItemCharged'       => $item_id,
                            ]);
                        }
                    }
                    
                    if($revenueID === 'ER' && $this->check_is_allow_medsys) {
                        MedsysCashAssessment::create([
                            'idNum'         => $case_no . 'B',
                            'HospNum'       => $patient_id,
                            'Name'          => $request->payload['patient_Name'] ?? null,
                            'TransDate'     => Carbon::now(),
                            'AssessNum'      => $assessNum,
                            'Indicator'     => $revenueID,
                            'DrCr'          => 'D',
                            'RecordStatus'  => 1,
                            'itemID'        => $item_id,
                            'Quantity'      => 1,
                            'RefNum'        => $sequence,
                            'Amount'        => $amount,
                            'UserId'        => $checkUser->idnumber,
                            'RevenueID'     => $revenueID,
                            'SpecimenId'    => $request->payload['SpecimenId'] ?? null
                        ]);
                    }
                }
            }

            if (isset($request->payload['DoctorCharges']) && count($request->payload['DoctorCharges']) > 0) {
                foreach ($request->payload['DoctorCharges'] as $doctorcharges) {
                    $revenueID = $doctorcharges['code'];
                    $item_id = $doctorcharges['doctor_code'];
                    $quantity = 1;
                    $amount = floatval(str_replace([',', '₱'], '', $doctorcharges['amount']));
                    $drcr = $doctorcharges['drcr'];
                    $lgrp = $doctorcharges['lgrp'];
                    $refnum[] = $sequence;

                    $saveCharges = HISBillingOut::create([
                        'patient_Id' => $patient_id,
                        'case_No' => $case_no,
                        'transDate' => $transDate,
                        'msc_price_scheme_id' => $msc_price_scheme_id,
                        'revenueID' => $revenueID,
                        'drcr' => $drcr,
                        'lgrp' => $lgrp,
                        'itemID' => $item_id,
                        'quantity' => $quantity,
                        'refNum' => $sequence,
                        'ChargeSlip' => $sequence,
                        'amount' => $amount,
                        'userId' => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'net_amount' => $amount,
                        'hostName' => (new GetIP())->getHostname(),
                        'accountnum' => $guarantor_Id,
                        'auto_discount' => 0,
                    ]);
                    if ($saveCharges && $this->check_is_allow_medsys) {
                        MedSysDailyOut::create([
                            'HospNum'           => $patient_id,
                            'IDNum'             => $case_no . 'B',
                            'TransDate'         => $transDate,
                            'RevenueID'         => $revenueID,
                            'ItemID'            => $item_id,
                            'DrCr'              => $drcr,
                            'Quantity'          => $quantity,
                            'RefNum'            => $sequence,
                            'ChargeSlip'        => $sequence,
                            'Amount'            => $amount,
                            'UserID'            => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'HostName'          => (new GetIP())->getHostname(),
                            'AutoDiscount'      => 0,
                        ]);
                    }
                }
            }

            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_laboratory')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            DB::connection('sqlsrv_medsys_laboratory')->commit();
            $data['charges'] =  $this->history($patient_id, $case_no, 'all', $refnum);
            return response()->json(['message' => 'Charges posted successfully', 'data' => $data], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_laboratory')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function revokecharge(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_laboratory')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_medsys_laboratory')->beginTransaction();
        try {
            $checkUser = null;
            if (isset($request->user_passcode) && isset($request->user_passcode)) {
                $checkUser = User::where([['idnumber', '=', $request->user_passcode], ['passcode', '=', $request->user_passcode]])->first();
                if (!$checkUser) {
                    return response()->json([
                        'message' => 'Incorrect Username or Password',
                    ], 404);
                }
            }

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
                        'quantity' => $existingData->quantity * -1,
                        'refNum' => $existingData->refNum,
                        'amount' => $existingData->amount * -1,
                        'userId' => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'net_amount' => $existingData->net_amount * -1,
                        'HostName' => (new GetIP())->getHostname(),
                        'accountnum' => $existingData->accountnum,
                        'auto_discount' => 0,
                    ]);
                    if ($this->check_is_allow_medsys) {
                        MedSysDailyOut::create([
                            'HospNum'           => $existingData->patient_Id,
                            'IDNum'             => $existingData->case_No . 'B',
                            'TransDate'         => Carbon::now(),
                            'RevenueID'         => $existingData->revenueID,
                            'DrCr'              => 'C',
                            'Quantity'          => $existingData->quantity * -1,
                            'RefNum'            => $existingData->refNum,
                            'ChargeSlip'        => $existingData->refNum,
                            'Amount'            => $existingData->amount * -1,
                            'UserID'            => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'HostName'          => (new GetIP())->getHostname(),
                            'AutoDiscount'      => 0,
                        ]);
                    }

                    if ($existingData->revenueID == 'LB') {
                        LaboratoryMaster::where('patient_Id', $patient_id)
                            ->where('case_No', $case_no)
                            ->where('refNum', $refnum)
                            ->where('itemID', $item_id)
                            ->update([
                                'request_Status'    => 'R',
                                'result_Status'     => 'R',
                        ]);
                        if ($this->check_is_allow_medsys) {
                            tbLABMaster::where('HospNum', $patient_id)
                                ->where('IdNum', $case_no . 'B')
                                ->where('RefNum', $refnum)
                                ->where('ItemId', $item_id)
                                ->update([
                                    'RequestStatus'     => 'R',
                                    'ResultStatus'      => 'R',
                            ]);
                        }
                    } 
                }
            }

            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_laboratory')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            DB::connection('sqlsrv_medsys_laboratory')->commit();
            return response()->json(['message' => 'Charges revoked successfully'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_laboratory')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
