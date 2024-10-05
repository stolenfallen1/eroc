<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\his_functions\CashAssessment;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HISCashAssestmentController extends Controller
{
    //
    public function getcashassessment(Request $request) 
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
    public function history($patient_id, $case_no, $code, $refNum = [])
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $query = CashAssessment::with('items', 'doctor_details')
                ->where('patient_Id', $patient_id)
                ->where('case_No', $case_no)
                ->where('quantity', '>', 0)
                ->whereDate('transdate', $today);

            if ($code == 'MD') {
                $query->whereIn('revenueID', ['MD']);
            } else if ($code == '') {
                $query->whereNotIn('revenueID', ['MD']);
            }
            if (count($refNum) > 0) {
                $query->whereIn('refNum', $refNum);
            }

            $query->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('CashAssessment as cancelled')
                    ->whereColumn('cancelled.patient_Id', 'CashAssessment.patient_Id')
                    ->whereColumn('cancelled.case_No', 'CashAssessment.case_No')
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
            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            
            if(!$checkUser):
                return response()->json([
                    'message' => 'Incorrect Username or Password',
                ], 404);
            endif;

            $chargeslip_sequence = SystemSequence::where('code', 'GCN')->first();
            if (!$chargeslip_sequence) {
                throw new \Exception('Charge Slip Sequence not found');
            } 
            $assessnum_sequence = SystemSequence::where('code', 'GAN')->first();
            if (!$assessnum_sequence) {
                throw new \Exception('Assessment Number Sequence not found');
            } 

            $patient_id = $request->payload['patient_Id'];
            $case_no = $request->payload['case_No'];
            $patient_name = $request->payload['patient_Name'];
            $requesting_doctor_id = $request->payload['attending_Doctor'];
            $requesting_doctor_name = $request->payload['attending_Doctor_fullname'];
            $transdate = Carbon::now();
            $refNum = [];
            if (isset($request->payload['Charges']) && count($request->payload['Charges']) > 0) {
                foreach ($request->payload['Charges'] as $charge) {
                    $revenueID = $charge['code'];
                    $itemID = $charge['map_item_id'];
                    $quantity = $charge['quantity'];
                    $amount = floatval(str_replace([',', '₱'], '', $charge['price']));
                    $specimenId = $charge['specimen'];
                    $sequence = $revenueID . $chargeslip_sequence->seq_no;
                    $barcode_prefix = $charge['barcode_prefix'] ?? null;

                    if ($barcode_prefix === null) {
                        $barcode = '';
                    } else {
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
                    }

                    $refNum[] = $sequence;
                    CashAssessment::create([
                        'branch_id' => 1,
                        'patient_Id' => $patient_id,
                        'case_No' => $case_no,
                        'patient_Name' => $patient_name,
                        'transdate' => $transdate,
                        'assessnum' => $assessnum_sequence->seq_no,
                        'drcr' => 'C',
                        'revenueID' => $revenueID,
                        'itemID' => $itemID,
                        'quantity' => $quantity,
                        'refNum' => $sequence,
                        'amount' => $amount,
                        'specimenId' => $specimenId,
                        'requestDoctorID' => $requesting_doctor_id,
                        'requestDoctorName' => $requesting_doctor_name,
                        'departmentID' => $revenueID,
                        'Barcode' => $barcode,
                        'userId' => $checkUser->idnumber,
                        'hostname' => (new GetIP())->getHostname(),
                        'createdBy' => $checkUser->idnumber,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

            if (isset($request->payload['DoctorCharges']) && count($request->payload['DoctorCharges']) > 0) {
                foreach ($request->payload['DoctorCharges'] as $doctorcharges) {
                    $revenueID = $doctorcharges['code'];
                    $itemID = $doctorcharges['doctor_code'];
                    $quantity = 1;
                    $amount = floatval(str_replace([',', '₱'], '', $doctorcharges['amount']));
                    $sequence = $revenueID . $chargeslip_sequence->seq_no;
                    $refNum[] = $sequence;
                    CashAssessment::create([
                        'branch_id' => 1,
                        'patient_id' => $patient_id,
                        'case_no' => $case_no,
                        'patient_name' => $patient_name,
                        'transdate' => $transdate,
                        'assessnum' => $assessnum_sequence->seq_no,
                        'drcr' => 'C',
                        'revenueID' => $revenueID,
                        'itemID' => $itemID,
                        'quantity' => $quantity,
                        'refNum' => $sequence,
                        'amount' => $amount,
                        'requestDoctorID' => $requesting_doctor_id,
                        'requestDoctorName' => $requesting_doctor_name,
                        'departmentID' => $revenueID,
                        'userId' => $checkUser->idnumber,
                        'hostname' => (new GetIP())->getHostname(),
                        'createdBy' => $checkUser->idnumber,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

            $chargeslip_sequence->update([
                'seq_no' => $chargeslip_sequence->seq_no + 1,
                'recent_generated' => $chargeslip_sequence->seq_no
            ]);
            $assessnum_sequence->update([
                'seq_no' => $assessnum_sequence->seq_no + 1,
                'recent_generated' => $assessnum_sequence->seq_no
            ]);
            DB::commit();
            $data['charges'] = $this->history($patient_id, $case_no, 'all', $refNum);
            return response()->json(['message' => 'Charges posted successfully', 'data' => $data], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function revokecashassessment(Request $request) 
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
                $refNum = $item['refNum'];
                $itemID = $item['itemID'];

                $existingData = CashAssessment::where('patient_Id', $patient_id) 
                    ->where('case_No', $case_no)
                    ->where('refNum', $refNum)
                    ->where('itemID', $itemID)
                    ->first();

                $existingData->updateOrFail([
                    'dateRevoked' => Carbon::now(),
                    'revokedBy' => $checkUser->idnumber,
                ]);

                if ($existingData) {
                    CashAssessment::create([
                        'patient_Id' => $existingData->patient_Id,
                        'case_No' => $existingData->case_No,
                        'patient_name' => $existingData->patient_name,
                        'transdate' => Carbon::now(),
                        'drcr' => 'C',
                        'revenueID' => $existingData->revenueID,
                        'itemID' => $existingData->itemID,
                        'quantity' => -1,
                        'refNum' => $existingData->refNum,
                        'amount' => $existingData->amount * -1,
                        'requestDoctorID' => $existingData->requestDoctorID,
                        'requestDoctorName' => $existingData->requestDoctorName,
                        'departmentID' => $existingData->departmentID,
                        'userId' => $checkUser->idnumber,
                        'hostname' => (new GetIP())->getHostname(),
                        'createdBy' => $checkUser->idnumber,
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
