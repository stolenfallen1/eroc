<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\HIS\his_functions\NurseCommunicationFile;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\tbNurseCommunicationFile;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\MMIS\inventory\InventoryTransaction;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\HIS\medsys\tbInvStockCard;
use Illuminate\Http\Request;

class AncillaryController extends Controller
{
    //
    protected $check_is_allow_medsys;
    public function __construct() 
    {
        $this->check_is_allow_medsys = (new SysGlobalSetting())->check_is_allow_medsys_status();
    }
    public function getOPDOrders() 
    {
        try {
            $nurseLogData = NurseLogBook::where('patient_Type', 'O')
                ->where('record_Status', 'X')
                ->where('issupplies', 1)
                ->where('revenue_Id', 'CS')
                ->orderBy('createdat', 'desc')
                ->get()
                ->groupBy('requestNum');

            $formattedData = [];

            foreach ($nurseLogData as $requestNum => $records) {
                $firstRecord = $records->first();

                $formattedData[] = [
                    'patient_Id'        => $firstRecord->patient_Id,
                    'case_No'           => $firstRecord->case_No,
                    'patient_Name'      => $firstRecord->patient_Name,
                    'requestNum'        => $firstRecord->requestNum,
                    'items'             => $records->map(function ($record) {
                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'patient_Type' => $record->patient_Type,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'Quantity' => $record->Quantity,
                            'item_OnHand' => $record->item_OnHand,
                            'item_ListCost' => $record->item_ListCost,
                            'section_Id' => $record->section_Id,
                            'price' => $record->price,
                            'amount' => $record->amount,
                            'record_Status' => $record->record_Status,
                            'user_Id' => $record->user_Id,
                            'request_Date' => $record->request_Date,
                            'station_Id' => $record->station_Id,
                            'remarks' => $record->remarks,
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
            $nurseLogData = NurseLogBook::where('patient_Type', 'E')
                ->where('record_Status', 'X')
                ->where('issupplies', 1)
                ->where('revenue_Id', 'CS')
                ->orderBy('createdat', 'desc')
                ->get()
                ->groupBy('requestNum');

            $formattedData = [];

            foreach ($nurseLogData as $requestNum => $records) {
                $firstRecord = $records->first();

                $formattedData[] = [
                    'patient_Id'        => $firstRecord->patient_Id,
                    'case_No'           => $firstRecord->case_No,
                    'patient_Name'      => $firstRecord->patient_Name,
                    'requestNum'        => $firstRecord->requestNum,
                    'items'             => $records->map(function ($record) {
                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'patient_Type' => $record->patient_Type,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'Quantity' => $record->Quantity,
                            'item_OnHand' => $record->item_OnHand,
                            'item_ListCost' => $record->item_ListCost,
                            'section_Id' => $record->section_Id,
                            'price' => $record->price,
                            'amount' => $record->amount,
                            'record_Status' => $record->record_Status,
                            'user_Id' => $record->user_Id,
                            'request_Date' => $record->request_Date,
                            'station_Id' => $record->station_Id,
                            'remarks' => $record->remarks,
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
            $nurseLogData = NurseLogBook::where('patient_Type', 'I')
                ->where('record_Status', 'X')
                ->where('issupplies', 1)
                ->where('revenue_Id', 'CS')
                ->orderBy('createdat', 'desc')
                ->get()
                ->groupBy('requestNum');

            $formattedData = [];

            foreach ($nurseLogData as $requestNum => $records) {
                $firstRecord = $records->first();

                $formattedData[] = [
                    'patient_Id'        => $firstRecord->patient_Id,
                    'case_No'           => $firstRecord->case_No,
                    'patient_Name'      => $firstRecord->patient_Name,
                    'requestNum'        => $firstRecord->requestNum,
                    'items'             => $records->map(function ($record) {
                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'patient_Type' => $record->patient_Type,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'Quantity' => $record->Quantity,
                            'item_OnHand' => $record->item_OnHand,
                            'item_ListCost' => $record->item_ListCost,
                            'section_Id' => $record->section_Id,
                            'price' => $record->price,
                            'amount' => $record->amount,
                            'record_Status' => $record->record_Status,
                            'user_Id' => $record->user_Id,
                            'request_Date' => $record->request_Date,
                            'station_Id' => $record->station_Id,
                            'remarks' => $record->remarks,
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
                            'LocationID'            => 21,
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
                            'requestNum'        => $requestNum . '[REVOKED]',
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
                            'requestNum'        => $requestNum . '[REVOKED]',
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
                                    'RequestNum'        => $requestNum . '[REVOKED]',
                                    'RecordStatus'      => 'R',
                                ]);

                            tbNurseCommunicationFile::where('Hospnum', $patient_Id)
                                ->where('IDnum', $case_No . 'B')
                                ->where('RequestNum', $requestNum)
                                ->where('ItemID', $item_Id)
                                ->update([
                                    'Remarks'           => $remarks,
                                    'RequestNum'        => $requestNum . '[REVOKED]',
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
}
