<?php

namespace App\Http\Controllers\HIS;

use App\Http\Controllers\Controller;
use App\Models\HIS\PatientHistory;
use App\Models\HIS\services\PatientRegistry;
use App\Models\User;
use App\Helpers\GetIP;
use Illuminate\Http\Request;
use \Carbon\Carbon;
use App\Models\HIS\ErResult;
use DB;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\SOA\OutPatient;
use App\Models\HIS\MedsysAdmittingCommunication;
use App\Models\HIS\AdmittingCommunicationFile;
use App\Helpers\HIS\SysGlobalSetting;
use App\Models\Profesional\Doctors;
use App\Models\HIS\mscPatientStatus;

class PatientDischarge extends Controller
{

    protected $check_is_allow_medsys;

    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    //
    public function mayGoHome(Request $request, $id) {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        // try {

            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            if(!$checkUser):
                return response()->json([$message='Incorrect Username or Password'], 404);
            endif;

            $registry_data = [
                'queue_Number'              => 0,
                'mscDisposition_Id'         => $request->payload['mscDisposition_Id'],
                'mscCase_Result_Id'         => $request->payload['ERpatient_result'],
                'mgh_Userid'                => $checkUser->idnumber,
                'mgh_Datetime'              => Carbon::now(),
                'mgh_Hostname'              => (new GetIP())->getHostname(),
                'isreferredFrom'            => intval($request->mscDisposition_Id) === 3 ? 1 : 0,
                'referred_From_HCI'         => $request->payload['refered_Form_HCI'] ?? null,
                'referred_From_HCI_address' => $request->payload['FromHCIAddress'] ?? null,
                'referred_From_HCI_code'    => $request->payload['refered_From_HCI_code'] ?? null,
                'referred_To_HCI'           => $request->payload['refered_To_HCI'] ?? null,
                'referred_To_HCI_address'   => $request->payload['ToHCIAddress'] ?? null,
                'referred_To_HCI_code'      => $request->payload['refered_To_HCI_code'] ?? null,
                'referring_Doctor'          => $request->payload['refering_Doctor'] ?? null,
                'referral_Reason'           => $request->payload['referal_Reason'] ?? null,
                'typeOfDeath_id'            => $request->payload['typeOfDeath_id'] ?? null,
                'dateOfDeath'               => $request->payload['dateOfDate'] ?? null,
                'updatedBy'                 => $checkUser->idnumber,
                'updated_at'                => Carbon::now()
            ];

            $history_data = [
                'impression'            => $request->payload['initial_impression'] ?? null,
                'discharge_Diagnosis'   => $request->payload['discharge_diagnosis'] ?? null
            ];

            $patient_registry = PatientRegistry::where('case_No', $id)->first();

            if($patient_registry) {

                $updated_registry   = $patient_registry->update($registry_data);
                $updated_history    = $patient_registry->history()->update($history_data);

                if(!$updated_registry && !$updated_history) {
                    throw new \Exception('Error');
                } 

                DB::connection('sqlsrv_patient_data')->commit();

                return response()->json([
                    'message' => 'Patient is tagged as may go home successfully'
                ], 200);
            }

        // } catch(\Exception $e) {
            
        //     DB::connection('sqlsrv_patient_data')->rollBack();
        //     return response()->json([
        //         'message' => 'Failed to update patient registry or patient history'
        //     ], 500);
        // }
    }


    public function dischargePatient(Request $request, $id) {

        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();

        try {

            $checkUser = User::where([['idnumber', '=', $request->payload['user_userid']], ['passcode', '=', $request->payload['user_passcode']]])->first();
            
            if(!$checkUser):
                return response()->json([$message='Incorrect Username or Password'], 404);
            endif;

            $patientRegistry = PatientRegistry::where('case_No', $id)->first();

            if(!$patientRegistry && $patientRegistry->mgh_Userid === '' && intval($patientRegistry->isHoldReg) === 1 ) {
                return response()->json([
                    'message' => 'This patient has not yet been tagged as eligible for discharge.'
                ], 404);
            }

            $patient_id = $patientRegistry->patient_Id;
            $case_No = $patientRegistry->case_No;
            $OPDIDnum = $patientRegistry->case_No . 'B';

            $registry_data = [
                'discharged_Userid'         => $checkUser->idnumber,
                'discharged_Date'           => Carbon::now(),
                'discharged_Hostname'       => (new GetIP())->getHostname(),
                'dischargeNotice_UserId'    => $patientRegistry->mgh_Userid,
                'dischargeNotice_Date'      => $patientRegistry->mgh_Datetime,
                'dischargeNotice_Hostname'  => $patientRegistry->mgh_Hostname,
                'discharge_Diagnosis'       => $request->payload['discharge_Diagnosis'],
                'updatedBy'                 => $checkUser->idnumber,
                'updated_at'                => Carbon::now()
            ];

            $send_to_CDG_ComFile_data = [
                'patient_Id'    => $patient_id,
                'case_No'       => $case_No,
                'requestDate'   =>Carbon::now(),
                'requestBy'     => $checkUser->idnumber,
                'createdBy'     => $checkUser->idnumber,
                'createdat'     => Carbon::now(),
                'updatedby'     => $checkUser->idnumber,
                'updatedat'     => Carbon::now()
            ];

            $send_to_Medsys_CompFile_data = [
                'HospNum'       => $patient_id,
                'OPDIDnum'      => $OPDIDnum,
                'RequestDate'   =>Carbon::now(),
                'RequestBy'     => $checkUser->idnumber   
            ];

            $patient_registry = PatientRegistry::where('case_No', $id)->first();

            if($patient_registry) {

                if($this->check_is_allow_medsys) {
                    $is_To_Medsys = MedsysAdmittingCommunication::updateOrCreate(['OPDIDnum' => $OPDIDnum], $send_to_Medsys_CompFile_data);
                } else {
                    return response()->json(['message' => 'Medsys does not allow to save data' ], 500);
                }

                if($is_To_Medsys) {
                    $discharged_patient = $patient_registry->update($registry_data);
                    $is_To_CDG = AdmittingCommunicationFile::updateOrCreate(['case_No' => $case_No],  $send_to_CDG_ComFile_data);
                }

                if(!$discharged_patient || !$is_To_CDG) {
                    throw new \Exception('Error');
                }
            }

            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_patient_data')->commit();

            return response()->json([
                'message' => 'Patient has been discharged successfuly'
            ], 200);

        } catch(\Exception $e) {

            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();

            return response()->json([
                'message' => 'Failed to Discharged Patient, Pleas call IT Department'
            ], 500);
        }
       
    }

    public function erResult() {
        try {
            $data = ErResult::select('id', 'description')
                            ->orderBy('description','asc')
                            ->get();
            if($data->isEmpty()) {
                return response()->json([
                    'message'   => 'No data is Found!'
                ], 404);
            }

            return response()->json($data, 200);

        } catch(\Exception $e) {
            return response()->json([
                'message'   => 'Error' . $e->getMessage()
            ], 500);
        }
    }

    public function untagMGH(Request $request, $id) {

        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_patient_data')->beginTransaction();
        
        try {

            $checkUser = User::where([
                ['idnumber', '=', $request->payload['user_userid']], 
                ['passcode', '=', $request->payload['user_passcode']]
            ])->first();
    
            if (!$checkUser) {

                return response()->json(['message' => 'Incorrect Username or Password'], 404);
                
            }
    
            $patientRegistry = PatientRegistry::where('case_No', $id)->first();
       
            if ($patientRegistry && !$patientRegistry->discharged_Userid) {
                
                $registry_data = [
                    'queue_Number'          => 0,
                    'mscDisposition_Id'     => null,
                    'mgh_Userid'            => null,
                    'mgh_Datetime'          => null,
                    'mgh_Hostname'          => null,
                    'untag_Mgh_Userid'      => $checkUser->idnumber,
                    'untag_Mgh_Datetime'    => Carbon::now(),
                    'untag_Mgh_Hostname'    => (new GetIP())->getHostname(),
                    'updatedBy'             => $checkUser->idnumber,
                    'updated_at'            => Carbon::now()
                ];
        
                $history_data = [
                    'impression'            => null,
                    'discharge_Diagnosis'   => null,
                    'updatedby'             => $checkUser->idnumber,
                    'updated_at'            => Carbon::now()
                ];
    
                $updated_registry = $patientRegistry->update($registry_data);
                $updated_history  = $patientRegistry->history()->where('case_No', $id)->update($history_data);
                
                if ($updated_registry && $updated_history) {

                    DB::connection('sqlsrv_patient_data')->commit();
                    DB::connection('sqlsrv_medsys_patient_data')->commit();
        
                    return response()->json(['message' => 'Patient discharge approval has been successfully canceled.'], 200);
                }
            } else {

                return response()->json(['message' => 'Patient has already been discharged.'], 400);
            }
    
        } catch(\Exception $e) {

            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_medsys_patient_data')->rollBack();

            return response()->json(['message' => 'Error! ' . $e->getMessage()], 500);
        }
    }

    public function getTotalCharges($id) {

        $data = OutPatient::with(['patientBillingInfo' => function($query) {
            $query->orderBy('revenueID', 'asc'); 
                }])
                ->where('case_No', $id)
                ->take(1)
                ->get();
        
        if($data->isEmpty()) {

            return response()->json([
                'message'   => 'Cannot issue statement, billing is empty'
            ], 404);

        } else {
           
            $billsPayment = DB::connection('sqlsrv_billingOut')->select('EXEC sp_billing_SOACompleteSummarized ?', [$id]);

            $totalChargesSummary = collect($billsPayment)
                ->groupBy('RevenueID') 
                ->map(function($groupedItems) {

                    $totalAmount = $groupedItems->sum(function($billing) {
                        return floatval(str_replace(',', '', $billing->Charges ?? 0));
                    });
                });

                $firstRow = true;
                $runningBalance = 0; 
                $totalCharges = 0;
                $prevID = '';
                
                $patientBill = $data->flatMap(function($item) use (&$firstRow, &$runningBalance, &$totalCharges, &$prevID) {

                    return $item->patientBillingInfo->map(function($billing) use (&$firstRow, &$runningBalance, &$totalCharges, &$prevID) {

                        $charges = floatval(str_replace(',', '', ($billing->amount * intval($billing->quantity))));  
                
                        if ($firstRow && ($billing->drcr === 'D' || $billing->drcr === 'P')) {

                            $runningBalance = $charges;
                            $totalCharges = $charges;
                            $firstRow = false;

                        } elseif($firstRow && $billing->drcr === 'C') {

                            $runningBalance = 0;
                            $totalCharges = 0;
                            $firstRow = false;

                        } else {

                            if ((strcasecmp($billing->drcr, 'D') === 0 || strcasecmp($billing->drcr, 'P') === 0) && strcasecmp($billing->revenueID, $prevID) !== 0) {

                                $runningBalance = 0;
                                $runningBalance += $charges;
                                $totalCharges += $charges;

                            } elseif((strcasecmp($billing->drcr, 'D') === 0 || strcasecmp($billing->drcr, 'P') === 0) && strcasecmp($billing->revenueID, $prevID)=== 0) {

                                $runningBalance += $charges;
                                $totalCharges += $charges;

                            } else {

                                $runningBalance -= $charges;
                                $totalCharges -= $charges;
                            }

                        }

                        $prevID = $billing->revenueID;

                        return [
                            
                            'Charges'               => isset($billing->drcr) && ($billing->drcr === 'C' || intval($billing->quantity) <= 0)
                                                    ? number_format(0, 2)
                                                    : number_format($charges,2),

                            'Credit'                => isset($billing->drcr) && ($billing->drcr === 'C' || intval($billing->quantity) <= 0) 
                                                    ? number_format($charges,2)
                                                    : number_format(0, 2),

                            'Balance'               => number_format($runningBalance, 2)
                        ];

                    });
                });

                
                $totalCharges = $patientBill->sum(function($bill) {
                    return floatval(str_replace(',', '', $bill['Charges']));
                });
                
                $totalCredits = $patientBill->sum(function($bill) {
                    return floatval(str_replace(',', '', $bill['Credit']));
                });
    
                $patientBillInfo = [
                    'Charges' => number_format($totalCharges, 2),
                    'Credit' => number_format($totalCredits, 2),
                    'Total_Charges'     =>  number_format($totalCharges, 2),
                ]; 

            return response()->json($patientBillInfo, 200);

        }
        
    }

    public function getDoctorsList() {
        try {
            $query = Doctors::select('id', 'doctor_code', 'lastname', 'firstname', 'middlename')
                            ->where('isactive', 1);
    
            if (Request()->keyword) {
                $query->where('lastname', 'LIKE', '%' . Request()->keyword . '%')
                    ->orWhere('firstname', 'LIKE', '%' . Request()->keyword . '%')
                    ->orWhere('doctor_code', 'LIKE', '%' . Request()->keyword . '%');
            }
            $query->orderBy('id', 'asc');
            $page = Request()->per_page ?? '50';
            return response()->json($query->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function getPatientStatusList() {
        try {
            $patientStatus = mscPatientStatus::select('id', 'description')
                                                ->where('isactive', 1)
                                                ->orderBy('id', 'asc')
                                                ->get();
            return response()->json($patientStatus, 200);
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPatientChargesStatus($id) {
        try {
            $patientRegistry = PatientRegistry::where('case_No', $id)->first();

            if(!$patientRegistry) {
                return response()->json(['message' => 'Record Not Found'], 404);
            }

            if($patientRegistry->guarantor_Name === 'Self Pay') {
                $query = DB::table('CDG_BILLING.dbo.CashAssessment as ca')
                ->select(
                    'ca.patient_Id',
                    'ca.case_No',
                    'ca.revenueID as revenue_Id',
                    'ca.recordStatus',
                    'ca.ORNumber',
                )
                ->where('ca.case_No', '=', $id);
            } else {
                $query = DB::table('CDG_PATIENT_DATA.dbo.NurseLogBook as cdgLB')
                    ->select(
                        'cdgLB.patient_Id',
                        'cdgLB.case_No',
                        'cdgLB.revenue_Id',
                        'cdgLB.record_Status as recordStatus',
                    )
                    ->where('cdgLB.case_No', '=', $id);
            }
            $dataCharges = $query->get();
            if($dataCharges->isEmpty()) {
                return response()->json(['message' => 'No pending charges or request'], 404);
            }
            return response()->json($dataCharges, 200);
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }
    
    
}
