<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FmsExamProcedureItems;
use App\Models\HIS\BillingOutModel;
use App\Models\HIS\his_functions\ExamLaboratoryProfiles;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\LaboratoryExamsView;
use App\Models\HIS\his_functions\LaboratoryMaster;
use App\Models\HIS\his_functions\NurseCommunicationFile;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\MedSysDailyOut;
use App\Models\HIS\medsys\tbLABMaster;
use App\Models\HIS\medsys\tbNurseCommunicationFile;
use App\Models\HIS\medsys\tbNurseLogBook;
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
            $data = PatientRegistry::with('patient_details')
                ->whereHas('lab_services', function($query) {
                    $query->whereNotNull('case_No')
                        ->where('request_Status', '!=', 'X')
                        ->where('result_Status', '!=', 'X')
                        ->whereDate('processed_Date', Carbon::today());
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
                    $query->whereNotNull('case_No')
                        ->where('request_Status', '!=', 'X')
                        ->where('result_Status', '!=', 'X')
                        ->whereDate('processed_Date', Carbon::today());
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
                    $query->whereNotNull('case_No')
                        ->where('request_Status', '!=', 'X')
                        ->where('result_Status', '!=', 'X')
                        ->whereDate('processed_Date', Carbon::today());
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
    public function getOPDPendingLabRequest()
    {
        try {
            $data = PatientRegistry::with(['patient_details', 'lab_services'])
                ->whereHas('lab_services', function ($query) {
                    $query->whereNotNull('case_No')
                        ->where('request_Status', 'X')
                        ->where('result_Status', 'X');
                })
                ->where('mscAccount_Trans_Types', 2)
                ->get()
                ->map(function ($patient) {
                    $labServicesWithDescriptions = $patient->lab_services->filter(function ($service) {
                        return $service->request_Status == 'X' && $service->result_Status == 'X';
                    })->map(function ($service) {
                        $examDetails = LaboratoryExamsView::where('itemcharged', $service->profileId)
                            ->where('itemid', $service->itemId)
                            ->first();
                        $service->description = $examDetails ? $examDetails->exam : 'No description';
                        return $service;
                    });
    
                    $groupedByRefNum = $labServicesWithDescriptions->groupBy('refNum');
    
                    return [
                        'patient_Id' => $patient->patient_Id,
                        'case_No' => $patient->case_No,
                        'patient_Name' => $patient->patient_details->lastname . ', ' . $patient->patient_details->firstname . ' ' . $patient->patient_details->middlename,
                        'lab_services' => $groupedByRefNum
                    ];
                });
    
            $flattenedData = $data->flatMap(function ($patient) {
                return $patient['lab_services']->map(function ($services, $refNum) use ($patient) {
                    $groupedByProfileId = $services->groupBy('profileId');
    
                    return [
                        'patient_Id' => $patient['patient_Id'],
                        'case_No' => $patient['case_No'],
                        'patient_Name' => $patient['patient_Name'],
                        'refNum' => $refNum, 
                        'lab_services' => $groupedByProfileId,  
                    ];
                });
            });
    
            return response()->json($flattenedData->values(), 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getERPendingLabRequest() 
    {
        try {
            $data = PatientRegistry::with(['patient_details', 'lab_services'])
                ->whereHas('lab_services', function ($query) {
                    $query->whereNotNull('case_No')
                        ->where('request_Status', 'X')
                        ->where('result_Status', 'X');
                })
                ->where('mscAccount_Trans_Types', 5)
                ->get()
                ->map(function ($patient) {
                    $labServicesWithDescriptions = $patient->lab_services->filter(function ($service) {
                        return $service->request_Status == 'X' && $service->result_Status == 'X';
                    })->map(function ($service) {
                        $examDetails = LaboratoryExamsView::where('itemcharged', $service->profileId)
                            ->where('itemid', $service->itemId)
                            ->first();
                        $service->description = $examDetails ? $examDetails->exam : 'No description';
                        return $service;
                    });
    
                    $groupedByRefNum = $labServicesWithDescriptions->groupBy('refNum');
    
                    return [
                        'patient_Id' => $patient->patient_Id,
                        'case_No' => $patient->case_No,
                        'patient_Name' => $patient->patient_details->lastname . ', ' . $patient->patient_details->firstname . ' ' . $patient->patient_details->middlename,
                        'lab_services' => $groupedByRefNum
                    ];
                });
    
            $flattenedData = $data->flatMap(function ($patient) {
                return $patient['lab_services']->map(function ($services, $refNum) use ($patient) {
                    $groupedByProfileId = $services->groupBy('profileId');
    
                    return [
                        'patient_Id' => $patient['patient_Id'],
                        'case_No' => $patient['case_No'],
                        'patient_Name' => $patient['patient_Name'],
                        'refNum' => $refNum, 
                        'lab_services' => $groupedByProfileId,  
                    ];
                });
            });
    
            return response()->json($flattenedData->values(), 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getIPDPendingLabRequest() 
    {
        try {
            $data = PatientRegistry::with(['patient_details', 'lab_services'])
                ->whereHas('lab_services', function ($query) {
                    $query->whereNotNull('case_No')
                        ->where('request_Status', 'X')
                        ->where('result_Status', 'X');
                })
                ->where('mscAccount_Trans_Types', 6)
                ->get()
                ->map(function ($patient) {
                    $labServicesWithDescriptions = $patient->lab_services->filter(function ($service) {
                        return $service->request_Status == 'X' && $service->result_Status == 'X';
                    })->map(function ($service) {
                        $examDetails = LaboratoryExamsView::where('itemcharged', $service->profileId)
                            ->where('itemid', $service->itemId)
                            ->first();
                        $service->description = $examDetails ? $examDetails->exam : 'No description';
                        return $service;
                    });
    
                    $groupedByRefNum = $labServicesWithDescriptions->groupBy('refNum');
    
                    return [
                        'patient_Id' => $patient->patient_Id,
                        'case_No' => $patient->case_No,
                        'patient_Name' => $patient->patient_details->lastname . ', ' . $patient->patient_details->firstname . ' ' . $patient->patient_details->middlename,
                        'lab_services' => $groupedByRefNum
                    ];
                });
    
            $flattenedData = $data->flatMap(function ($patient) {
                return $patient['lab_services']->map(function ($services, $refNum) use ($patient) {
                    $groupedByProfileId = $services->groupBy('profileId');
    
                    return [
                        'patient_Id' => $patient['patient_Id'],
                        'case_No' => $patient['case_No'],
                        'patient_Name' => $patient['patient_Name'],
                        'refNum' => $refNum, 
                        'lab_services' => $groupedByProfileId,  
                    ];
                });
            });
    
            return response()->json($flattenedData->values(), 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function carryOrder(Request $request) 
    {
        DB::connection('sqlsrv_laboratory')->beginTransaction();
        DB::connection('sqlsrv_medsys_laboratory')->beginTransaction();
        try {
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $refNum = $request->payload['refNum'];

            if (isset($request->payload['Orders']) && count($request->payload['Orders']) > 0) {
                foreach ($request->payload['Orders'] as $items) {
                    $profileId = $items['profileId'];
                    $itemId = $items['itemId'];

                    LaboratoryMaster::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('refNum', $refNum)
                        ->where('profileId', $profileId)
                        ->where('itemId', $itemId)
                        ->where('request_Status', 'X')
                        ->where('result_Status', 'X')
                        ->update([
                            'request_Status' => 'W',
                            'result_Status' => 'W',
                            'processed_By' => Auth()->user()->idnumber,
                            'processed_Date' => Carbon::now(),
                            'updatedby' => Auth()->user()->idnumber,
                            'updated_at' => Carbon::now(),
                        ]);
                    NurseLogBook::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $refNum)
                        ->where('item_Id', $profileId)
                        ->where('record_Status', 'X')
                        ->update([
                            'record_Status'     => 'W',
                            'process_By'        => Auth()->user()->idnumber,
                            'process_Date'      => Carbon::now(),
                            'updatedby'         => Auth()->user()->idnumber,
                            'updatedat'         => Carbon::now(),
                        ]);
                    NurseCommunicationFile::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $refNum)
                        ->where('item_Id', $profileId)
                        ->where('record_Status', 'X')
                        ->update([
                            'record_Status'     => 'W',
                            'updatedby'         => Auth()->user()->idnumber,
                            'updatedat'         => Carbon::now(),
                        ]);
                    if ($this->check_is_allow_medsys):
                        tbLABMaster::where('HospNum', $patient_Id)
                            ->where('IdNum', $case_No . 'B')
                            ->where('RefNum', $refNum)
                            ->where('ProfileId', $profileId)
                            ->where('ItemId', $itemId)
                            ->where('RequestStatus', 'X')
                            ->where('ResultStatus', 'X')
                            ->update([
                                'RequestStatus' => 'W',
                                'ResultStatus' => 'W',
                            ]);
                        tbNurseLogBook::where('Hospnum', $patient_Id)
                            ->where('IDnum', $case_No . 'B')
                            ->where('RequestNum', $refNum)
                            ->where('ItemID', $profileId)
                            ->where('RecordStatus', 'X')
                            ->update([
                                'RecordStatus'      => 'W',
                                'ProcessBy'         => Auth()->user()->idnumber,
                                'ProcessDate'       => Carbon::now(),
                            ]);
                        tbNurseCommunicationFile::where('Hospnum', $patient_Id)
                            ->where('IDnum', $case_No . 'B')
                            ->where('RequestNum', $refNum)
                            ->where('ItemID', $profileId)
                            ->where('RecordStatus', 'X')
                            ->update([
                                'RecordStatus'      => 'W',
                            ]);
                        endif;
                }
            }

            DB::connection('sqlsrv_laboratory')->commit();
            DB::connection('sqlsrv_medsys_laboratory')->commit();
            return response()->json(['message' => 'Order carried successfully'], 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_laboratory')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            return response()->json([
                'message' => 'Failed to carry order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function cancelOrder(Request $request) 
    {
        DB::connection('sqlsrv_laboratory')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_billing')->beginTransaction();
        DB::connection('sqlsrv_medsys_laboratory')->beginTransaction();

        try {
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $refNum = $request->payload['refNum'];
            $patient_Type = $request->payload['patient_Type'];
            $remarks = $request->payload['remarks'];

            if (isset($request->payload['Orders']) && count($request->payload['Orders']) > 0) {
                foreach ($request->payload['Orders'] as $items) {
                    $profileId = $items['profileId'];
                    $itemId = $items['itemId'];

                    LaboratoryMaster::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('refNum', $refNum)
                        ->where('profileId', $profileId)
                        ->where('itemId', $itemId)
                        ->where('request_Status', 'X')
                        ->where('result_Status', 'X')
                        ->update([
                            'request_Status' => 'R',
                            'result_Status' => 'R',
                            'remarks' => $remarks,
                            'canceled_By' => Auth()->user()->idnumber,
                            'canceled_Date' => Carbon::now(),
                            'updatedby' => Auth()->user()->idnumber,
                            'updated_at' => Carbon::now(),
                        ]);
                    NurseLogBook::where('patient_ID', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $refNum)
                        ->where('item_Id', $profileId)
                        ->update([
                            'remarks'           => $remarks,
                            'record_Status'     => 'R',
                            'requestNum'        => $refNum . '[REVOKED]',
                            'cancelBy'          => Auth()->user()->idnumber,
                            'cancelDate'        => Carbon::now(),
                            'updatedby'         => Auth()->user()->idnumber,
                            'updatedat'         => Carbon::now(),
                        ]);
                    NurseCommunicationFile::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $refNum)
                        ->where('item_Id', $profileId)
                        ->update([
                            'remarks'           => $remarks,
                            'record_Status'     => 'R',
                            'requestNum'        => $refNum . '[REVOKED]',
                            'cancelBy'          => Auth()->user()->idnumber,
                            'cancelDate'        => Carbon::now(),
                            'updatedby'         => Auth()->user()->idnumber,
                            'updatedat'         => Carbon::now(),
                        ]);
            
                    if ($this->check_is_allow_medsys):
                        tbLABMaster::where('HospNum', $patient_Id)
                            ->where('IdNum', $case_No . 'B')
                            ->where('RefNum', $refNum)
                            ->where('ProfileId', $profileId)
                            ->where('ItemId', $itemId)
                            ->where('RequestStatus', 'X')
                            ->where('ResultStatus', 'X')
                            ->update([
                                'RequestStatus' => 'R',
                                'ResultStatus' => 'R',
                                'Remarks' => $remarks,
                            ]);
                        tbNurseLogBook::where('Hospnum', $patient_Id)
                            ->where('IDnum', $case_No . 'B')
                            ->where('RequestNum', $refNum)
                            ->where('ItemID', $profileId)
                            ->update([
                                'Remarks'           => $remarks,
                                'RequestNum'        => $refNum . '[REVOKED]',
                                'RecordStatus'      => 'R',
                            ]);
                        tbNurseCommunicationFile::where('Hospnum', $patient_Id)
                            ->where('IDnum', $case_No . 'B')
                            ->where('RequestNum', $refNum)
                            ->where('ItemID', $profileId)
                            ->update([
                                'Remarks'           => $remarks,
                                'RequestNum'        => $refNum . '[REVOKED]',
                                'RecordStatus'      => 'R',
                            ]);
                    endif;

                    $existingData = HISBillingOut::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('itemID', $profileId)
                        ->where('refNum', $refNum)
                        ->first();

                    if ($existingData):
                        $existingData->update([
                            'refNum'        => $refNum . '[REVOKED]',
                            'ChargeSlip'    => $refNum . '[REVOKED]',
                        ]);
                        MedSysDailyOut::where('HospNum', $patient_Id)
                            ->where('IDNum', $case_No . 'B')
                            ->where('ItemID', $profileId)
                            ->where('RefNum', $refNum)
                            ->update([
                                'RefNum' => $refNum . '[REVOKED]',
                            ]);

                        HISBillingOut::create([
                            'patient_Id'                => $existingData->patient_Id ?? $patient_Id, 
                            'case_No'                   => $existingData->case_No ?? $case_No,
                            'patient_Type'              => $existingData->patient_Type ?? $patient_Type,
                            'accountnum'                => $existingData->accountnum,
                            'transDate'                 => Carbon::now(),
                            'msc_price_scheme_id'       => $existingData->msc_price_scheme_id,
                            'revenueID'                 => $existingData->revenueID ?? 'LB',
                            'drcr'                      => $existingData->drcr,
                            'lgrp'                      => $existingData->lgrp,
                            'itemID'                    => $existingData->itemID ?? $profileId,
                            'quantity'                  => $existingData->quantity * -1,
                            'refNum'                    => $existingData->refNum,
                            'ChargeSlip'                => $existingData->ChargeSlip,
                            'amount'                    => $existingData->amount * -1,
                            'net_amount'                => $existingData->net_amount * -1,
                            'userId'                    => $existingData->userId,
                            'record_status'             => 'R',
                            'Barcode'                   => null,
                            'created_at'                => Carbon::now(),
                            'createdby'                 => Auth()->user()->idnumber,
                        ]);
                        MedSysDailyOut::create([
                            'HospNum'                   => $existingData->patient_Id ?? $patient_Id,
                            'IDNum'                     => $existingData->case_No . 'B' ?? $case_No . 'B',
                            'TransDate'                 => Carbon::now(),
                            'RevenueID'                 => $existingData->revenueID,
                            'DrCr'                      => $existingData->drcr,
                            'ItemID'                    => $existingData->itemID ?? $profileId,
                            'Quantity'                  => $existingData->quantity * -1,
                            'RefNum'                    => $existingData->refNum,
                            'Amount'                    => $existingData->amount * -1,
                            'UserID'                    => $existingData->userId,
                        ]);
                    endif;
                }
            }
            DB::connection('sqlsrv_laboratory')->commit();
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_billing')->commit();
            DB::connection('sqlsrv_medsys_laboratory')->commit();
            return response()->json(['message' => 'Order cancelled successfully'], 200);


        } catch (\Exception $e) {
            DB::connection('sqlsrv_laboratory')->rollBack();
            DB::connection('sqlsrv_billingOut')->rollBack();
            DB::connection('sqlsrv_medsys_billing')->rollBack();
            DB::connection('sqlsrv_medsys_laboratory')->rollBack();
            return response()->json([
                'message'   => 'Failed to cancel order',
                'error'     => $e->getMessage(),
                'line'      => $e->getLine(),
                'file'      => $e->getFile()
            ], 500);
        }
    }
    public function checkPatientLabStatusRequest(Request $request) 
    {
        // Sa medsys, nga in-ani nga function sa ilaha legend wala gi apil ang mga wala pa na bayran ( if cash assessment / cash based transaction)
        try {
            $query = LaboratoryExamsView::query()
                ->whereNotNull('caseno')
                ->where('requestStatus', '!=', 'X')
                ->where('resultStatus', '!=', 'X');
    
            if ($request->has('case_No') && ($request->case_No != null || $request->case_No != '')) {
                $query->where('caseno', $request->case_No);
            }
    
            if ($request->has('lastname') && ($request->lastname != null || $request->lastname != '')) {
                $query->where('lastname', 'like', '%' . $request->lastname . '%'); 
            }
    
            if ($request->has('filter_date')) {
                $filterDate = $request->filter_date;
                if ($filterDate == 'Today') {
                    $query->whereDate('renderdate', Carbon::today())
                        ->orWhereDate('cancelleddate', Carbon::today());
                } elseif ($filterDate == 'Yesterday') {
                    $query->whereDate('renderdate', Carbon::yesterday())
                        ->orWhereDate('cancelleddate', Carbon::yesterday());
                } else {
                    throw new \Exception('Invalid filter date');
                }
            }
    
            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('renderdate', [$request->date_from, $request->date_to])
                    ->orWhereBetween('cancelleddate', [$request->date_from, $request->date_to]);
            }

            // // Optionally, filter by today's date if no filter options is used ( So technically during first load / open sa user kanang wala pa siyay gi use as filter options )
            if (!$request->has('case_No') && !$request->has('lastname') && !$request->has('filter_date') && !$request->has('date_from') && !$request->has('date_to')) {
                $query->whereDate('renderdate', Carbon::today())
                    ->orWhereDate('cancelleddate', Carbon::today());
            }
    
            $data = $query->orderBy('refNum', 'desc')->get();
            return response()->json($data, 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to check patient lab status request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
