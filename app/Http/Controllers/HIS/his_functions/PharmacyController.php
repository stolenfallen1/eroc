<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\HIS\his_functions\NurseCommunicationFile;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\tbInvStockCard;
use App\Models\HIS\medsys\tbNurseCommunicationFile;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\HIS\mscDosages;
use App\Models\HIS\services\PatientRegistry;
use App\Models\MMIS\inventory\InventoryTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyController extends Controller
{
    //
    protected $check_is_allow_medsys;
    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    public function getMedicineCodes() 
    {
        return TransactionCodes::query()
            ->where('isMedicine', 1)
            ->pluck('code');
    }
    public function getOPDOrders() 
    {
        try {
            $medicineCodes = $this->getMedicineCodes();
    
            $nurseLogData = NurseLogBook::where('patient_Type', 'O')
                ->where('record_Status', 'X')
                ->whereIn('revenue_Id', $medicineCodes)
                ->orderBy('createdat', 'desc')
                ->get()
                ->groupBy('requestNum');
    
            $dosages = mscDosages::all()->keyBy('dosage_id'); 
    
            $formattedData = [];
    
            foreach ($nurseLogData as $requestNum => $records) {
                $firstRecord = $records->first();
    
                $formattedData[] = [
                    'patient_Id' => $firstRecord->patient_Id,
                    'case_No' => $firstRecord->case_No,
                    'requestNum' => $requestNum,
                    'patient_Name' => $firstRecord->patient_Name,
                    'items' => $records->map(function($record) use ($dosages) {
                        $dosageData = $dosages->get($record->dosage, ['dosage_id' => 'N/A', 'description' => 'N/A', 'frequency' => 'N/A']);

                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'patient_Type' => $record->patient_Type,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'specimen_Id' => $record->specimen_Id,
                            'Quantity' => $record->Quantity,
                            'item_OnHand' => $record->item_OnHand,
                            'item_ListCost' => $record->item_ListCost,
                            'dosage' => [
                                'dosage_id' => $dosageData['dosage_id'],
                                'description' => $dosageData['description'],
                                'frequency' => $dosageData['frequency'],
                            ],
                            'section_Id' => $record->section_Id,
                            'price' => $record->price,
                            'amount' => $record->amount,
                            'record_Status' => $record->record_Status,
                            'user_Id' => $record->user_Id,
                            'request_Date' => $record->request_Date,
                            'station_Id' => $record->station_Id,
                            'remarks' => $record->remarks,
                            'stat' => $record->stat,
                            'dcrdate' => $record->dcrdate,
                        ];
                    })->toArray()
                ];
            }
    
            return response()->json($formattedData, 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], 500);
        }
    }
    public function getEROrders() 
    {
        try {
            $medicineCodes = $this->getMedicineCodes();
    
            $nurseLogData = NurseLogBook::where('patient_Type', 'E') // Kung unsa para sa ER / Emergency Room ( Depends ni Sir Joe )
                ->where('record_Status', 'X')
                ->whereIn('revenue_Id', $medicineCodes)
                ->orderBy('createdat', 'desc')
                ->get()
                ->groupBy('requestNum');
    
            $dosages = mscDosages::all()->keyBy('dosage_id'); 
    
            $formattedData = [];
    
            foreach ($nurseLogData as $requestNum => $records) {
                $firstRecord = $records->first();
    
                $formattedData[] = [
                    'patient_Id' => $firstRecord->patient_Id,
                    'case_No' => $firstRecord->case_No,
                    'requestNum' => $requestNum,
                    'patient_Name' => $firstRecord->patient_Name,
                    'items' => $records->map(function($record) use ($dosages) {
                        $dosageData = $dosages->get($record->dosage, ['dosage_id' => 'N/A', 'description' => 'N/A', 'frequency' => 'N/A']);

                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'patient_Type' => $record->patient_Type,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'specimen_Id' => $record->specimen_Id,
                            'Quantity' => $record->Quantity,
                            'item_OnHand' => $record->item_OnHand,
                            'item_ListCost' => $record->item_ListCost,
                            'dosage' => [
                                'dosage_id' => $dosageData['dosage_id'],
                                'description' => $dosageData['description'],
                                'frequency' => $dosageData['frequency'],
                            ],
                            'section_Id' => $record->section_Id,
                            'price' => $record->price,
                            'amount' => $record->amount,
                            'record_Status' => $record->record_Status,
                            'user_Id' => $record->user_Id,
                            'request_Date' => $record->request_Date,
                            'station_Id' => $record->station_Id,
                            'remarks' => $record->remarks,
                            'stat' => $record->stat,
                            'dcrdate' => $record->dcrdate,
                        ];
                    })->toArray()
                ];
            }
    
            return response()->json($formattedData, 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], 500);
        }
    }
    public function getIPDOrders() 
    {
        try {
            $medicineCodes = $this->getMedicineCodes();
    
            $nurseLogData = NurseLogBook::where('patient_Type', 'I') // Or kung unsa para sa IPD / Inpatient ( Depends ni Sir Joe )
                ->where('record_Status', 'X')
                ->whereIn('revenue_Id', $medicineCodes)
                ->orderBy('createdat', 'desc')
                ->get()
                ->groupBy('requestNum');
    
            $dosages = mscDosages::all()->keyBy('dosage_id'); 
    
            $formattedData = [];
    
            foreach ($nurseLogData as $requestNum => $records) {
                $firstRecord = $records->first();
    
                $formattedData[] = [
                    'patient_Id' => $firstRecord->patient_Id,
                    'case_No' => $firstRecord->case_No,
                    'requestNum' => $requestNum,
                    'patient_Name' => $firstRecord->patient_Name,
                    'items' => $records->map(function($record) use ($dosages) {
                        $dosageData = $dosages->get($record->dosage, ['dosage_id' => 'N/A', 'description' => 'N/A', 'frequency' => 'N/A']);

                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'patient_Type' => $record->patient_Type,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'specimen_Id' => $record->specimen_Id,
                            'Quantity' => $record->Quantity,
                            'item_OnHand' => $record->item_OnHand,
                            'item_ListCost' => $record->item_ListCost,
                            'dosage' => [
                                'dosage_id' => $dosageData['dosage_id'],
                                'description' => $dosageData['description'],
                                'frequency' => $dosageData['frequency'],
                            ],
                            'section_Id' => $record->section_Id,
                            'price' => $record->price,
                            'amount' => $record->amount,
                            'record_Status' => $record->record_Status,
                            'user_Id' => $record->user_Id,
                            'request_Date' => $record->request_Date,
                            'station_Id' => $record->station_Id,
                            'remarks' => $record->remarks,
                            'stat' => $record->stat,
                            'dcrdate' => $record->dcrdate,
                        ];
                    })->toArray()
                ];
            }
    
            return response()->json($formattedData, 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], 500);
        }
    }
    public function carryOrder(Request $request) 
    {
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();

        try {
            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $requestNum = $request->payload['requestNum'];

            if (isset($request->payload['Orders']) && count($request->payload['Orders']) > 0) {
                foreach ($request->payload['Orders'] as $items) {
                    $warehouse_Id = $items['warehouse_Id'];
                    $revenue_Id = $items['revenue_Id'];
                    $item_Id = $items['item_Id'];
                    $item_ListCost = $items['item_ListCost'];
                    $item_OnHand = $items['item_OnHand'];
                    $price = $items['price'];
                    $quantity = (array_key_exists('Carry_Quantity', $items) && $items['Carry_Quantity'] !== "") ? $items['Carry_Quantity'] : $items['quantity'];
                    $frequency = $items['frequency'];
                    $amount = $items['amount'];

                    // get reference num
                    $referenceNum = NurseLogBook::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $requestNum)
                        ->where('item_Id', $item_Id)
                        ->value('referenceNum');
                    // get reference num fallback value from different table
                    if (!$referenceNum) {
                        $referenceNum = NurseCommunicationFile::where('patient_Id', $patient_Id)
                            ->where('case_No', $case_No)
                            ->where('requestNum', $requestNum)
                            ->where('item_Id', $item_Id)
                            ->value('referenceNum');
                    }

                    $updateLogBook = NurseLogBook::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $requestNum)
                        ->where('item_Id', $item_Id)
                        ->update([
                            'record_Status'     => 'W',
                            'process_By'        => Auth()->user()->idnumber,
                            'process_Date'      => $today,
                            'updatedat'         => $today,
                            'updatedby'         => Auth()->user()->idnumber,
                        ]);

                    $updateCommunicationFile = NurseCommunicationFile::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $requestNum)
                        ->where('item_Id', $item_Id)
                        ->update([
                            'record_Status'     => 'W',
                            'updatedby'         => Auth()->user()->idnumber,
                            'updatedat'         => $today,
                        ]);

                    if ($updateLogBook && $updateCommunicationFile) {
                        InventoryTransaction::create([
                            'branch_Id'                             => 1,
                            'patient_Id'                            => $patient_Id,
                            'patient_Registry_Id'                   => $case_No,
                            'warehouse_Id'                          => $warehouse_Id,
                            'transaction_Item_Id'                   => $item_Id,
                            'transaction_Date'                      => $today,
                            'trasanction_Reference_Number'          => $referenceNum,
                            'transaction_Acctg_TransType'           => $revenue_Id,
                            'transaction_Qty'                       => $quantity,
                            'transaction_Item_OnHand'               => $item_OnHand,
                            'transaction_Item_ListCost'             => $item_ListCost,
                            'transaction_Item_SellingAmount'        => $price,
                            'transaction_Item_TotalAmount'          => $amount,
                            'transaction_Item_Med_Frequency_Id'     => $frequency,
                            'transaction_UserID'                    => Auth()->user()->idnumber,
                            'created_at'                            => $today,
                            'createdBy'                             => Auth()->user()->idnumber,
                        ]);
                    }

                    if ($this->check_is_allow_medsys):
                        tbNurseLogBook::where('Hospnum', $patient_Id)
                            ->where('IDnum', $case_No . 'B')
                            ->where('RequestNum', $requestNum)
                            ->where('ItemID', $item_Id)
                            ->update([
                                'RecordStatus'      => 'W',
                                'ProcessBy'         => Auth()->user()->idnumber,
                                'ProcessDate'       => $today,
                            ]);

                        tbNurseCommunicationFile::where('Hospnum', $patient_Id)
                            ->where('IDnum', $case_No . 'B')
                            ->where('RequestNum', $requestNum)
                            ->where('itemID', $item_Id)
                            ->update([
                                'RecordStatus'      => 'W',
                            ]);

                        tbInvStockCard::create([
                            'SummaryCode'           => $revenue_Id,
                            'HospNum'               => $patient_Id,
                            'IdNum'                 => $case_No . 'B',
                            'ItemID'                => $item_Id,
                            'TransDate'             => $today,
                            'RevenueID'             => $revenue_Id,
                            'RefNum'                => $referenceNum,
                            'Quantity'              => $quantity,
                            'Balance'               => $item_OnHand,
                            'NetCost'               => $item_ListCost,
                            'Amount'                => $amount,
                            'UserID'                => Auth()->user()->idnumber,
                            'RequestByID'           => Auth()->user()->idnumber,
                            'LocationID'            => 20,
                        ]);
                    endif;
                }
            }
            DB::connection('sqlsrv_medsys_inventory')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            return response()->json(['message' => 'Order Carried Successfully'], 200);
        
        } catch (\Exception $e) {
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], 500);
        }
    }
    public function cancelOrder(Request $request) 
    {
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();

        try {
            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $requestNum = $request->payload['requestNum'];
            $remarks = $request->payload['remarks'];

            if (isset($request->payload['Orders']) && count($request->payload['Orders']) > 0) {
                foreach ($request->payload['Orders'] as $items) {
                    $item_Id = $items['item_Id'];

                    $updateLogBook = NurseLogBook::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $requestNum)
                        ->where('item_Id', $item_Id)
                        ->update([
                            'remarks'           => $remarks,
                            'record_Status'     => 'R',
                            'cancelBy'          => Auth()->user()->idnumber,
                            'cancelDate'        => $today,
                            'updatedat'         => $today,
                            'updatedby'         => Auth()->user()->idnumber,
                        ]);

                    $updateCommunicationFile = NurseCommunicationFile::where('patient_Id', $patient_Id)
                        ->where('case_No', $case_No)
                        ->where('requestNum', $requestNum)
                        ->where('item_Id', $item_Id)
                        ->update([
                            'remarks'           => $remarks,
                            'record_Status'     => 'R',
                            'cancelBy'          => Auth()->user()->idnumber,
                            'cancelDate'        => $today,
                            'updatedat'         => $today,
                            'updatedby'         => Auth()->user()->idnumber,
                        ]);

                    if ($updateLogBook && $updateCommunicationFile) {
                        if ($this->check_is_allow_medsys): 
                            tbNurseLogBook::where('Hospnum', $patient_Id)
                                ->where('IDnum', $case_No . 'B')
                                ->where('RequestNum', $requestNum)
                                ->where('ItemID', $item_Id)
                                ->update([
                                    'Remarks'           => $remarks,
                                    'RecordStatus'      => 'R',
                                ]);

                            tbNurseCommunicationFile::where('Hospnum', $patient_Id)
                                ->where('IDnum', $case_No . 'B')
                                ->where('RequestNum', $requestNum)
                                ->where('ItemID', $item_Id)
                                ->update([
                                    'Remarks'           => $remarks,
                                    'RecordStatus'      => 'R',
                                ]);
                        endif;
                    }
                }
            }
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            return response()->json(['message' => 'Order Cancelled Successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getPostedMedicineByCaseNo(Request $request) 
    {
        try {
            $case_No = $request->query('case_No');
            $patient_details = PatientRegistry::where('case_No', $case_No)->first();

            if (!$patient_details) {
                return response()->json(['msg' => 'Patient not found'], 404);
            }

            $medicine_data = InventoryTransaction::with('nurse_logbook')
                ->where('patient_Registry_Id', $case_No)
                ->whereHas('nurse_logbook', function ($query) {
                    $query->where('record_Status', 'W');
                })
                ->orderBy('created_at', 'desc')
                ->get();

                $response = [
                    'patient_details' => [
                        'patient_Id' => $patient_details->patient_Id,
                        'case_No' => $patient_details->case_No,
                        'age' => $patient_details->patient_Age,
                        'doctor' => $patient_details->attending_Doctor_fullname,
                        'discharged_Userid' => $patient_details->discharged_Userid,
                        'discharged_Date' => $patient_details->discharged_Date,
                        'inventory_data' => $medicine_data->toArray(),
                    ],
                ];

                return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function postReturnMedicine(Request $request) 
    {
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();

        try {
            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $patient_Name = $request->payload['patient_Name'];
            $remarks = $request->payload['remarks'];

            if (isset($request->payload['Items']) && count($request->payload['Items']) > 0) {
                foreach ($request->payload['Items'] as $items) {
                    if (isset($items['nurse_logbook'])) {
                        $requestNum = $items['nurse_logbook']['requestNum'];
                        $referenceNum = $items['nurse_logbook']['referenceNum'];
                        $patient_Type = $items['nurse_logbook']['patient_Type'];
                        $description = $items['nurse_logbook']['description'];
                    }
                    $warehouse_Id = $items['warehouse_Id'];
                    $revenue_Id = $items['transaction_Acctg_TransType'];
                    $item_Id = $items['transaction_Item_Id'];
                    $list_cost = $items['transaction_Item_ListCost'];
                    $price = $items['transaction_Item_SellingAmount'];
                    $return_total_amount = $items['return_total_amount'];
                    $Quantity_To_return = $items['Quantity_To_return'];
                    
                    NurseLogBook::create([
                        'branch_id'             => 1,
                        'patient_Id'            => $patient_Id,
                        'case_No'               => $case_No,
                        'patient_Name'          => $patient_Name,
                        'patient_Type'          => $patient_Type,
                        'revenue_Id'            => $revenue_Id,
                        'requestNum'            => $requestNum,
                        'referenceNum'          => $referenceNum,
                        'item_Id'               => $item_Id,
                        'description'           => $description . '[RETURNED]',
                        'Quantity'              => $Quantity_To_return * -1,
                        'item_ListCost'         => $list_cost,
                        'price'                 => $price,
                        'amount'                => $return_total_amount * -1,
                        'record_Status'         => 'W',
                        'remarks'               => $remarks,
                        'section_Id'            => $warehouse_Id,
                        'user_Id'               => Auth()->user()->idnumber,
                        'process_By'            => Auth()->user()->idnumber,
                        'process_Date'          => $today,
                        'ismedicine'            => 1,
                        'createdat'             => $today,
                        'createdby'             => Auth()->user()->idnumber,
                    ]);
                    NurseCommunicationFile::create([
                        'branch_id'             => 1,
                        'patient_Id'            => $patient_Id,
                        'case_No'               => $case_No,
                        'patient_Name'          => $patient_Name,
                        'patient_Type'          => $patient_Type,
                        'item_Id'               => $item_Id,
                        'amount'                => $return_total_amount * -1,
                        'quantity'              => $Quantity_To_return * -1,
                        'section_Id'            => $warehouse_Id,
                        'request_Date'          => $today,
                        'revenue_Id'            => $revenue_Id,
                        'record_Status'         => 'W',
                        'remarks'               => $remarks,
                        'user_Id'               => Auth()->user()->idnumber,
                        'requestNum'            => $requestNum,
                        'referenceNum'          => $referenceNum,
                        'createdat'             => $today,
                        'createdby'             => Auth()->user()->idnumber,
                    ]);
                    InventoryTransaction::create([
                        'branch_Id'                             => 1,
                        'warehouse_Id'                          => $warehouse_Id,
                        'patient_Id'                            => $patient_Id,
                        'patient_Registry_Id'                   => $case_No,
                        'transaction_Item_Id'                   => $item_Id,
                        'transaction_Date'                      => $today,
                        'trasanction_Reference_Number'          => $referenceNum,
                        'transaction_Acctg_TransType'           => $revenue_Id,
                        'transaction_Qty'                       => $Quantity_To_return * -1,
                        'transaction_Item_ListCost'             => $list_cost,
                        'transaction_Item_SellingAmount'        => $price,
                        'transaction_Item_TotalAmount'          => $return_total_amount * -1,
                        'transaction_UserID'                    => Auth()->user()->idnumber,
                        'created_at'                            => $today,
                        'createdBy'                             => Auth()->user()->idnumber,
                    ]);
                    if ($this->check_is_allow_medsys) {
                        tbNurseLogBook::create([
                            'Hospnum'                   => $patient_Id,
                            'IDnum'                     => $case_No . 'B',
                            'PatientType'               => $patient_Type,
                            'RevenueID'                 => $revenue_Id,
                            'RequestDate'               => $today,
                            'ItemID'                    => $item_Id,
                            'Description'               => $description . '[RETURNED]',
                            'Quantity'                  => $Quantity_To_return * -1,
                            'Amount'                    => $return_total_amount * -1,
                            'RecordStatus'              => 'W',
                            'SectionID'                 => $warehouse_Id,
                            'UserID'                    => Auth()->user()->idnumber,
                            'ProcessBy'                 => Auth()->user()->idnumber,
                            'ProcessDate'               => $today,
                            'Remarks'                   => $remarks,
                            'RequestNum'                => $requestNum,
                            'ReferenceNum'              => $referenceNum,
                        ]);
                        tbNurseCommunicationFile::create([
                            'Hospnum'                   => $patient_Id,
                            'IDnum'                     => $case_No . 'B',
                            'PatientType'               => $patient_Type,
                            'ItemID'                    => $item_Id,
                            'Amount'                    => $return_total_amount * -1,
                            'Quantity'                  => $Quantity_To_return * -1,
                            'SectionID'                 => $warehouse_Id,
                            'RequestDate'               => $today,
                            'RevenueID'                 => $revenue_Id,
                            'RecordStatus'              => 'W',
                            'UserID'                    => Auth()->user()->idnumber,
                            'RequestNum'                => $requestNum,
                            'ReferenceNum'              => $referenceNum,
                            'Remarks'                   => $remarks,
                        ]);
                        tbInvStockCard::create([
                            'SummaryCode'               => 'PH',
                            'HospNum'                   => $patient_Id,
                            'IdNum'                     => $case_No . 'B',
                            'ItemID'                    => $item_Id,
                            'TransDate'                 => $today,
                            'RevenueID'                 => 'PH',
                            'RefNum'                    => $referenceNum,
                            'Quantity'                  => $Quantity_To_return * -1,
                            'NetCost'                   => $list_cost,
                            'Amount'                    => $return_total_amount * -1,
                            'UserID'                    => Auth()->user()->idnumber,
                            'LocationID'                => 20,
                        ]);
                    }
                }
                DB::connection('sqlsrv_medsys_inventory')->commit();
                DB::connection('sqlsrv_patient_data')->commit();
                DB::connection('sqlsrv_mmis')->commit();
                DB::connection('sqlsrv_medsys_nurse_station')->commit();
                return response()->json(['message' => 'Medicine Returned Successfully'], 200);
            }

        } catch (\Exception $e) {
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
