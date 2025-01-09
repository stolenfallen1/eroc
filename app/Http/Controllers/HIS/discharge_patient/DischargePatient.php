<?php

namespace App\Http\Controllers\HIS\discharge_patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HIS\services\PatientRegistry;
use App\Models\User;
use DB;
use App\Models\HIS\SOA\OutPatient;
use App\Helpers\HIS\SysGlobalSetting;
use App\Helpers\HIS\discharge_patient\DischargePatientHelper;

class DischargePatient extends Controller
{
    //
    protected $check_is_allow_medsys;
    protected $dischargePatientData;

    public function __construct() {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
        $this->dischargePatientData = new DischargePatientHelper();
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
            if($patientRegistry) {
                if($this->check_is_allow_medsys) {
                    if(intval($patientRegistry->mscDisposition_Id) === 10) {
                        $dischargePatient = $this->dischargePatientData->dischargedPatientForAdmission($patientRegistry, $checkUser, $request);
                    } else {
                        $dischargePatient = $this->dischargePatientData->processDischargedPatient($patientRegistry, $checkUser, $request);
                    }
                } else {
                    return response()->json(['message' => 'Medsys does not allow to save data' ], 500);
                }
            } else {
                return response()->json(['message' => 'Patient Not Found'], 404);
            }
            if(!$dischargePatient) {
                throw new \Exception('Failed to discharge patient');
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
                            'Charges'   => isset($billing->drcr) && ($billing->drcr === 'C' || intval($billing->quantity) <= 0)
                                        ? number_format(0, 2)
                                        : number_format($charges,2),
                            'Credit'             => isset($billing->drcr) && ($billing->drcr === 'C' || intval($billing->quantity) <= 0) 
                                        ? number_format($charges,2)
                                        : number_format(0, 2),
                            'Balance'   => number_format($runningBalance, 2)
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

    public function checkPatientEligibilityForDischarge($id) {
        try {
            $patientRegistry = PatientRegistry::where('case_No', $id)->first();
            if (!$patientRegistry) {
                return response()->json(['message' => 'Record Not Found'], 404);
            } 
            $hasMGH = $this->checkHasMGH($patientRegistry);
            $hasDischarged = $this->checkDischargePatient($patientRegistry);
            if(!$hasMGH && !$hasDischarged) {
                return response()->json(['isEligible' => 0, 'isDischarged' => 0], 200);
            } else if($hasMGH && $hasDischarged) {
                return response()->json(['isEligible' => 0, 'isDischarged' => 1], 200);
            } else {
                return response()->json(['isEligible' => 1, 'isDischarged' => 0], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'ERROR: ' . $e->getMessage()], 500);
        }
    }

    private function checkHasMGH($patientRegistry) {
        return !empty($patientRegistry->mgh_Userid) && !empty($patientRegistry->mgh_Datetime);
    }

    private function checkDischargePatient($patientRegistry) {
        return !empty($patientRegistry->discharged_Userid) && !empty($patientRegistry->discharged_Date);
    }
}
