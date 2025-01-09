<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\medsys\MedSysCashAssessment;
use Carbon\Carbon;
use App\Models\User;
use CashAssessmentSequence;
use GlobalChargingSequences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\Translation\t;

class HISCashAssestmentController extends Controller
{
    protected $check_is_allow_medsys;
    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
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
            default:
                return $barcode;
        }
        return $barcode;
    }
    public function getcashassessment(Request $request) 
    {
        try {
            $patient_id = $request->patient_Id;
            $case_no = $request->case_No;
            // $code = $request->code;
            $revenueCodes = $this->getRevenueCode($case_no);
            foreach ($revenueCodes as $code) {
                $data = $this->history($patient_id, $case_no, $code);
            }
            // $data = $this->history($patient_id, $case_no, $code);
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function history($patient_id, $case_no, $code, $refNum = []) {
        try {
            $today = Carbon::now()->format('Y-m-d');

            $query = CashAssessment::with(['doctor_details'])
                ->where('patient_Id', $patient_id)
                ->where('case_No', $case_no)
                ->where('quantity', '>', 0)
                ->where('ismedicine', 0)
                ->where('issupplies', 0)
                ->where('isprocedure', 0)
                ->whereDate('created_at', $today)
                ->whereRaw("refNum NOT LIKE '%\\[REVOKED\\]%' ESCAPE '\\'");

            if ($code == 'MD') {
                $query->whereIn('revenueID', ['MD']);
            } else {
                $query->where('revenueID', $code);
            }
            if (count($refNum) > 0) {
                $query->whereIn('refNum', $refNum);
            }
            $results = $query->get();
            $results->each(function ($result) use ($code) {
                $result->items = $result->items($code)->first();
            });

            return $results;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getRevenueCode($case_no) {
        $revenueCodes = CashAssessment::distinct('revenueID')
                    ->where('case_No', $case_no)
                    ->whereRaw("refNum NOT LIKE '%\\[REVOKED\\]%' ESCAPE '\\'")
                    ->pluck('revenueID')
                    ->toArray();
        return $revenueCodes;
    }
    public function cashassessment(Request $request) 
    {
        DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
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

            $cashAssessmentSequences = new GlobalChargingSequences();
            $cashAssessmentSequences->incrementSequence(); 

            if ($this->check_is_allow_medsys) {
                $assessnum_sequence = $cashAssessmentSequences->getSequence();
            } else {
                $chargeslip_sequence = SystemSequence::where('code', 'GCN')->first();
                $assessnum_sequence = SystemSequence::where('code', 'GAN')->first();

                if ($chargeslip_sequence && $assessnum_sequence) {
                    $chargeslip_sequence->increment('seq_no');
                    $chargeslip_sequence->increment('recent_generated');
                    $assessnum_sequence->increment('seq_no');
                    $assessnum_sequence->increment('recent_generated');
                } else {
                    throw new \Exception('Sequences not found');
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

            $sequenceGenerated      = [];
            $xr_us_codes            = ['XR', 'US'];
            $xr_us_incremented      = false;
            $patient_id             = $request->payload['patient_Id'];
            $case_no                = $request->payload['case_No'];
            $patient_name           = $request->payload['patient_Name'];
            $requesting_doctor_id   = $request->payload['attending_Doctor'];
            $requesting_doctor_name = $request->payload['attending_Doctor_fullname'];
            $patient_Type           = $request->payload['patient_Type'];
            $transdate = Carbon::now();
            
            if (isset($request->payload['Charges']) && count($request->payload['Charges']) > 0) {
                foreach ($request->payload['Charges'] as $charge) {
                    $revenueID          = $charge['code'];
                    $itemID             = $charge['map_item_id'];
                    $quantity           = $charge['quantity'];
                    $amount             = floatval(str_replace([',', '₱'], '', $charge['price']));
                    $exam_description   = $charge['exam_description'];
                    $specimenId         = $charge['specimen'];
                    $form               = $charge['form'] ?? null;
                    $charge_type        = $charge['charge_type'] ?? null;
                    $barcode_prefix     = $charge['barcode_prefix'] ?? null;

                    if (in_array($revenueID, $xr_us_codes)) {
                        if (!$xr_us_incremented) {
                            $cashAssessmentSequences->incrementSequence('XR'); // Increment XR and US sequence once only for both
                            $chargeslip_sequence = $cashAssessmentSequences->getSequence();
                            $xr_us_incremented = true;
                        }
                    } else {
                        if (!isset($sequenceGenerated[$revenueID])) {
                            $cashAssessmentSequences->incrementSequence($revenueID);
                            $chargeslip_sequence = $cashAssessmentSequences->getSequence();
                            $sequenceGenerated[$revenueID] = true;
                        }
                    }

                    if (array_key_exists($revenueID, $revenueCodeSequences)) {
                        $sequenceType   = $revenueCodeSequences[$revenueID];
                        $sequence       = $revenueID . ($this->check_is_allow_medsys && isset($chargeslip_sequence[$sequenceType]) 
                                        ? $chargeslip_sequence[$sequenceType] 
                                        : $chargeslip_sequence['seq_no']);
                    }
                    
                    if ($barcode_prefix === null) {
                        $barcode = '';
                    } else {
                        $barcode = $this->getBarCode($barcode_prefix, $sequence, $specimenId);
                    }
                    $refNum[] = $sequence;
                    $saveCashAssessment = CashAssessment::create([
                        'branch_id'             => 1,
                        'patient_Id'            => $patient_id,
                        'case_No'               => $case_no,
                        'patient_Name'          => $patient_name,
                        'patient_Type'          => $patient_Type == 'Out-Patient' ? 'O' : ($patient_Type == 'Emergency' ? 'E' : 'I'),
                        'transdate'             => $transdate,
                        'assessnum'             => $assessnum_sequence['MedSysCashSequence'], 
                        'drcr'                  => 'C',
                        'form'                  => $form,
                        'stat'                  => $charge_type,
                        'revenueID'             => $revenueID,
                        'itemID'                => $itemID,
                        'requestDescription'    => $exam_description,
                        'quantity'              => $quantity,
                        'refNum'                => $sequence,
                        'amount'                => $amount,
                        'specimenId'            => $specimenId,
                        'requestDoctorID'       => $requesting_doctor_id,
                        'requestDoctorName'     => $requesting_doctor_name,
                        'departmentID'          => $revenueID,
                        'Barcode'               => $barcode,
                        'userId'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'hostname'              => (new GetIP())->getHostname(),
                        'createdBy'             => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'created_at'            => Carbon::now(),
                    ]);

                    if ($saveCashAssessment && $this->check_is_allow_medsys):
                        MedSysCashAssessment::create([
                            'HospNum'               => $patient_id,
                            'IdNum'                 => $case_no . 'B',
                            'Name'                  => $patient_name,
                            'TransDate'             => $transdate,
                            'AssessNum'             => $assessnum_sequence['MedSysCashSequence'],
                            'DrCr'                  => 'C',
                            'ItemID'                => $itemID,
                            'Quantity'              => $quantity,
                            'RefNum'                => $sequence,
                            'ChargeSlip'            => $sequence,
                            'Amount'                => $amount,
                            'SpecimenId'            => $specimenId,
                            'Barcode'               => $barcode,
                            'STAT'                  => $charge_type,
                            'DoctorName'            => $requesting_doctor_name,
                            'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'RevenueID'             => $revenueID,
                            'DepartmentID'          => $revenueID,
                        ]);
                    endif;
                }
            }

            if (isset($request->payload['DoctorCharges']) && count($request->payload['DoctorCharges']) > 0) {
                foreach ($request->payload['DoctorCharges'] as $doctorcharges) {
                    $revenueID      = $doctorcharges['code'];
                    $itemID         = $doctorcharges['doctor_code'];
                    $doctor_name    = $doctorcharges['doctor_name'];
                    $quantity       = 1;
                    $amount         = floatval(str_replace([',', '₱'], '', $doctorcharges['amount']));
                    $refNum[]       = $sequence;

                    $saveCashAssessment = CashAssessment::create([
                        'branch_id'             => 1,
                        'patient_id'            => $patient_id,
                        'case_no'               => $case_no,
                        'patient_Type'          => $patient_Type == 'Out-Patient' ? 'O' : ($patient_Type == 'Emergency' ? 'E' : 'I'),
                        'patient_name'          => $patient_name,
                        'transdate'             => $transdate,
                        'assessnum'             => $assessnum_sequence['MedSysCashSequence'],
                        'drcr'                  => 'C',
                        'revenueID'             => $revenueID,
                        'itemID'                => $itemID,
                        'requestDescription'    => $doctor_name,
                        'quantity'              => $quantity,
                        'refNum'                => $sequence,
                        'amount'                => $amount,
                        'requestDoctorID'       => $requesting_doctor_id,
                        'requestDoctorName'     => $requesting_doctor_name,
                        'departmentID'          => $revenueID,
                        'userId'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'hostname'              => (new GetIP())->getHostname(),
                        'createdBy'             => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'created_at'            => Carbon::now(),
                    ]);
                    if ($saveCashAssessment && $this->check_is_allow_medsys) {
                        MedSysCashAssessment::create([
                            'HospNum'               => $patient_id,
                            'IdNum'                 => $case_no . 'B',
                            'Name'                  => $patient_name,
                            'TransDate'             => $transdate,
                            'AssessNum'             => $assessnum_sequence['MedSysCashSequence'],
                            'DrCr'                  => 'C',
                            'ItemID'                => $itemID,
                            'Quantity'              => $quantity,
                            'RefNum'                => $sequence,
                            'ChargeSlip'            => $sequence,
                            'Amount'                => $amount,
                            'DoctorName'            => $requesting_doctor_name,
                            'UserID'                => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'RevenueID'             => $revenueID,
                            'DepartmentID'          => $revenueID,
                        ]);
                    }
                    
                }
            }

            DB::connection('sqlsrv')->commit();
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            DB::connection('sqlsrv_medsys_laboratory')->commit();
            $data['charges'] = $this->history($patient_id, $case_no, 'all', $refNum);
            return response()->json(['message' => 'Charges posted successfully', 'data' => $data], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function revokecashassessment(Request $request) 
    {
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
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
                $refNum = $item['refNum'];
                $itemID = $item['itemID'];

                $existingData = CashAssessment::where('patient_Id', $patient_id) 
                    ->where('case_No', $case_no)
                    ->where('refNum', $refNum)
                    ->where('itemID', $itemID)
                    ->first();

                $existingData->updateOrFail([
                    'dateRevoked' => Carbon::now(),
                    'revokedBy' => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                    'updatedBy'     => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                    'updated_at'    => Carbon::now(),
                    'refNum' => $refNum . '[REVOKED]',
                ]);

                if ($existingData) {
                    CashAssessment::create([
                        'branch_id' => 1,
                        'patient_Id' => $existingData->patient_Id,
                        'case_No' => $existingData->case_No,
                        'patient_Name' => $existingData->patient_Name,
                        'assessnum' => $existingData->assessnum,
                        'transdate' => Carbon::now(),
                        'drcr' => 'C',
                        'revenueID' => $existingData->revenueID,
                        'itemID' => $existingData->itemID,
                        'quantity' => $existingData->quantity * -1,
                        'refNum' => $existingData->refNum,
                        'amount' => $existingData->amount * -1,
                        'specimenId' => $existingData->specimenId,
                        'requestDoctorID' => $existingData->requestDoctorID,
                        'requestDoctorName' => $existingData->requestDoctorName,
                        'departmentID' => $existingData->departmentID,
                        'userId' => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'Barcode' => null,
                        'hostname' => (new GetIP())->getHostname(),
                        'createdBy' => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                        'created_at' => Carbon::now(),
                    ]);

                    if ($this->check_is_allow_medsys) {
                        MedSysCashAssessment::where('HospNum', $patient_id)
                            ->where('IdNum', $case_no . 'B')
                            ->where('RefNum', $refNum)
                            ->where('ItemID', $itemID)
                            ->update([
                                'DateRevoked'   => Carbon::now(),
                                'RevokedBy'     => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                                'RefNum'        => $refNum . '[REVOKED]',
                        ]);
                        MedSysCashAssessment::create([
                            'HospNum'           => $existingData->patient_Id,
                            'IdNum'             => $existingData->case_No . 'B',
                            'Name'              => $existingData->patient_Name,
                            'TransDate'         => Carbon::now(),
                            'AssessNum'         => $existingData->assessnum,
                            'DrCr'              => 'C',
                            'ItemID'            => $existingData->itemID,
                            'Quantity'          => $existingData->quantity * -1,
                            'RefNum'            => $existingData->refNum,
                            'ChargeSlip'        => $existingData->refNum,
                            'Amount'            => $existingData->amount * -1,
                            'Barcode'           => null,
                            'STAT'              => null,
                            'SpecimenId'        => $existingData->specimenId,
                            'DoctorName'        => $existingData->requestDoctorName,
                            'UserID'            => $checkUser ? $checkUser->idnumber : Auth()->user()->idnumber,
                            'RevenueID'         => $existingData->revenueID,
                            'DepartmentID'      => $existingData->departmentID,
                        ]);
                    }
                }
            }

            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            return response()->json(['message' => 'Charges revoked successfully'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    } 
}
