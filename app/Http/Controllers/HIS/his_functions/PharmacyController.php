<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\medsys\tbInvStockCard;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\HIS\mscDosages;
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
            if ($this->check_is_allow_medsys) {
                $inventoryChargeSlip = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->increment('DispensingCSlip');
                if ($inventoryChargeSlip) {
                    $medSysReferenceNum = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->value('ChargeSlip');
                } else {
                    throw new \Exception("Failed to increment charge slips / transaction sequences");
                }
            } else {
                throw new \Exception('MedSys is not allowed, no sequence of our own tho.');
            }

            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['case_No'];
            $requestNum = $request->payload['requestNum'];
            $referenceNum = 'C' . $medSysReferenceNum . 'M';

            if (isset($request->payload['Orders']) && count($request->payload['Orders']) > 0) {
                foreach ($request->payload['Orders'] as $items) {
                    $warehouse_Id = $items['warehouse_Id'];
                    $revenue_Id = $items['revenue_Id'];
                    $item_Id = $items['item_Id'];
                    $item_ListCost = $items['item_ListCost'];
                    $item_OnHand = $items['item_OnHand'];
                    $price = $items['price'];
                    // $quantity = isset($items['Carry_Quantity']) && $items['Carry_Quantity'] != "" || $items['Carry_Quantity'] != null ? $items['Carry_Quantity'] : $items['quantity'];
                    $quantity = (array_key_exists('Carry_Quantity', $items) && $items['Carry_Quantity'] !== "") ? $items['Carry_Quantity'] : $items['quantity'];
                    $frequency = $items['frequency'];
                    $amount = $items['amount'];

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

                    if ($updateLogBook) {
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
                            // 'transaction_Remarks'                   => 'Dispensing',
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
                            // 'Remarks'               => 'Dispensing',
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
}
