<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\LaboratoryExamsView;
use App\Models\HIS\his_functions\LaboratoryMaster;
use App\Models\HIS\medsys\MedSysDailyOut;
use App\Models\HIS\medsys\tbLABMaster;
use App\Models\HIS\services\Patient;
use App\Models\HIS\services\PatientRegistry;
use App\Helpers\GetIP;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class LaboratoryController extends Controller
{
    protected $check_is_allow_medsys;

    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    public function getOPDPatients()
    {
        try {
            $today = Carbon::now();
            $data = PatientRegistry::with('patient_details')
                ->whereHas('lab_services', function($query) {
                    $query->whereNotNull('case_No');
                })
                ->where('mscAccount_Trans_Types', 2) 
                ->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getERPatients()
    {
        try {
            $data = PatientRegistry::with('patient_details')
                ->whereHas('lab_services', function($query) {
                    $query->whereNotNull('case_No');
                })
                ->where('mscAccount_Trans_Types', 5) 
                ->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getIPDPatient()
    {
        try {
            $data = PatientRegistry::with('patient_details')
                ->whereHas('lab_services', function($query) {
                    $query->whereNotNull('case_No');
                })
                ->where('mscAccount_Trans_Types', 6) 
                ->orderBy('id', 'desc');
            $page = Request()->per_page ?? '50';
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // For viewing patient lab exams ( Include the cancelled by lab personals not including the cancelled by cashier through OR cancellation and charge revocation )
    public function getAllLabExamsByPatient(Request $request) 
    {
        try {
            $patient_Id = $request->items['patient_Id'];
            $case_No = $request->items['case_No'];
            $trans_types = $request->items['mscAccount_Trans_Types'];
    
            $data = LaboratoryExamsView::query()
                ->where('patientid', $patient_Id)
                ->where('caseno', $case_No)
                ->where('trans_types', $trans_types)
                ->where(function ($query) {
                    $query->where('requestStatus', '!=', 'R')
                        ->orWhere(function ($q) {
                            $q->where('requestStatus', 'R')
                                ->whereNotNull('cancelledby')
                                ->whereNotNull('cancelleddate');
                        });
                })
                ->orderBy('refNum', 'desc');
    
            $page = $request->per_page;
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // For fetching Lab Exams that is not cancelled ( For request cancellation )
    public function getUncancelledLabExamsByPatient(Request $request) 
    {
        try {
            $patient_Id = $request->items['patient_Id'];
            $case_No = $request->items['case_No'];
            $trans_types = $request->items['mscAccount_Trans_Types'];
    
            $data = LaboratoryExamsView::query()
                ->where('patientid', $patient_Id)
                ->where('caseno', $case_No)
                ->where('trans_types', $trans_types)
                ->where('requestStatus', '!=', 'R')
                ->where('resultStatus', '!=', 'R')
                ->whereNull('cancelledby')
                ->whereNull('cancelleddate')
                ->orderBy('refNum', 'desc');
            
            $page = Request()->per_page;
            return response()->json($data->paginate($page), 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }    
    // This function is used to cancel patient lab items ( Staff access only )
    public function archivePatientLabItem(Request $request) 
    {
        DB::connection('sqlsrv_laboratory')->beginTransaction();
        DB::connection('sqlsrv_medsys_laboratory')->beginTransaction();
        try {
            $patient_Id = $request->items['patient_Id'];
            $case_No = $request->items['case_No'];
            $refNum = $request->items['refNum'];
            $itemcharged = $request->items['itemcharged'];
            $remarks = $request->items['remarks'];

            $data = LaboratoryMaster::where('patient_Id', $patient_Id)
                ->where('case_No', $case_No)
                ->where('refNum', $refNum)
                ->where('profileId', $itemcharged)
                ->where('item_Charged', $itemcharged)
                ->where('request_Status', 'X')
                ->where('result_Status', 'X')
                ->update([
                    'canceled_By'       => Auth()->user()->idnumber,
                    'canceled_Date'     => Carbon::now(),
                    'updatedby'         => Auth()->user()->idnumber,
                    'updated_at'        => Carbon::now(),
                    'request_Status'    => 'R',
                    'result_Status'     => 'R',
                    'remarks'           => $remarks,
                ]);
            
            if ($data) {
                if ($this->check_is_allow_medsys) {
                    tbLABMaster::where('HospNum', $patient_Id)
                        ->where('IdNum', $case_No)
                        ->where('RefNum', $refNum)
                        ->where('ProfileId', $itemcharged)
                        ->where('RequestStatus', 'X')
                        ->where('ResultStatus', 'X')
                        ->update([
                            'RequestStatus'     => 'R',
                            'ResultStatus'      => 'R',
                        ]);
                }

                $billingOutData = HISBillingOut::where('patient_Id', $patient_Id)
                    ->where('case_No', $case_No)
                    ->where('itemID', $itemcharged)
                    ->first();
                if ($billingOutData) {
                    HISBillingOut::create([
                        'patient_Id'            => $billingOutData->patient_Id,
                        'case_No'               => $billingOutData->case_No,
                        'accountnum'            => $billingOutData->accountnum,
                        'transDate'             => Carbon::now(),
                        'msc_price_scheme_id'   => $billingOutData->msc_price_scheme_id,
                        'revenueID'             => $billingOutData->revenueID,
                        'drcr'                  => 'C',
                        'lgrp'                  => $billingOutData->lgrp,
                        'itemID'                => $billingOutData->itemID,
                        'quantity'              => $billingOutData->quantity * -1,
                        'refNum'                => $billingOutData->refNum,
                        'ChargeSlip'            => $billingOutData->ChargeSlip,
                        'amount'                => $billingOutData->amount * -1,
                        'net_amount'            => $billingOutData->net_amount * -1,
                        'userId'                => Auth()->user()->idnumber,
                        'request_doctors_id'    => $billingOutData->request_doctors_id,
                        'auto_discount'         => $billingOutData->auto_discount,
                        'hostName'              => (new GetIP())->getHostname(),
                        'created_at'            => Carbon::now(),
                    ]);
                    if ($this->check_is_allow_medsys) {
                        MedSysDailyOut::create([
                            'HospNum'           => $billingOutData->patient_Id,
                            'IDNum'             => $billingOutData->case_No,
                            'TransDate'         => Carbon::now(),
                            'RevenueID'         => $billingOutData->revenueID,
                            'DrCr'              => 'C',
                            'Quantity'          => $billingOutData->quantity * -1,
                            'RefNum'            => $billingOutData->refNum,
                            'Amount'            => $billingOutData->amount * -1,
                            'UserID'            => Auth()->user()->idnumber,
                            'HostName'          => (new GetIP())->getHostname(),
                            'AutoDiscount'      => 0,
                        ]);
                    }
                }
            }

            DB::connection('sqlsrv_laboratory')->commit();
            DB::connection('sqlsrv_medsys_laboratory')->commit();
            return response()->json([
                'message' => 'Exam cancelled',
            ], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_laboratory')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            return response()->json([
                'message' => 'Failed to archive data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // This function is used to cancel patient lab items ( Admin / Head of Laboratory access only )
    public function cancelPatientLabItem(Request $request) 
    {

    }
    public function getDischargedPatientToday() 
    {
        try {
            // $today = Carbon::now();
            $data = PatientRegistry::with('patient_details')
                ->where('mscAccount_Trans_Types', 2)
                ->orderBy('id', 'asc')
                ->get();
            return response()->json([
                'message' => 'Data fetched',
                'data' => $data
            ], 200); 
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
