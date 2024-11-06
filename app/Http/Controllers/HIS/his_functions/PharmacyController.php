<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Models\BuildFile\FMS\TransactionCodes;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\mscDosages;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    //
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
                    'items' => $records->map(function($record) use ($dosages) {
                        $dosageData = $dosages->get($record->dosage, ['dosage_id' => 'N/A', 'description' => 'N/A', 'frequency' => 'N/A']);

                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'specimen_Id' => $record->specimen_Id,
                            'Quantity' => $record->Quantity,
                            'dosage' => [
                                'dosage_id' => $dosageData['dosage_id'],
                                'description' => $dosageData['description'],
                                'frequency' => $dosageData['frequency'],
                            ],
                            'section_Id' => $record->section_Id,
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
            return response()->json(['error' => $e->getMessage()], 500);
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
                    'items' => $records->map(function($record) use ($dosages) {
                        $dosageData = $dosages->get($record->dosage, ['dosage_id' => 'N/A', 'description' => 'N/A', 'frequency' => 'N/A']);

                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'specimen_Id' => $record->specimen_Id,
                            'Quantity' => $record->Quantity,
                            'dosage' => [
                                'dosage_id' => $dosageData['dosage_id'],
                                'description' => $dosageData['description'],
                                'frequency' => $dosageData['frequency'],
                            ],
                            'section_Id' => $record->section_Id,
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
            return response()->json(['error' => $e->getMessage()], 500);
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
                    'items' => $records->map(function($record) use ($dosages) {
                        $dosageData = $dosages->get($record->dosage, ['dosage_id' => 'N/A', 'description' => 'N/A', 'frequency' => 'N/A']);

                        return [
                            'id' => $record->id,
                            'branch_Id' => $record->branch_Id,
                            'revenue_Id' => $record->revenue_Id,
                            'referenceNum' => $record->referenceNum,
                            'item_Id' => $record->item_Id,
                            'description' => $record->description,
                            'specimen_Id' => $record->specimen_Id,
                            'Quantity' => $record->Quantity,
                            'dosage' => [
                                'dosage_id' => $dosageData['dosage_id'],
                                'description' => $dosageData['description'],
                                'frequency' => $dosageData['frequency'],
                            ],
                            'section_Id' => $record->section_Id,
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
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function carryOrder(Request $request) 
    {
        try {
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
