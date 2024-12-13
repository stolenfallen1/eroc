<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Helpers\HIS\SysGlobalSetting;
use App\Http\Controllers\Controller;
use App\Models\BuildFile\SystemSequence;
use App\Models\HIS\his_functions\HISBillingOut;
use App\Models\HIS\his_functions\NurseCommunicationFile;
use App\Models\HIS\his_functions\NurseLogBook;
use App\Models\HIS\his_functions\ViewIncomeReport;
use App\Models\HIS\medsys\MedSysDailyOut;
use App\Models\HIS\medsys\tbNurseCommunicationFile;
use App\Models\HIS\medsys\tbNurseLogBook;
use App\Models\HIS\services\PatientRegistry;
use App\Models\MMIS\inventory\InventoryTransaction;
use Auth;
use Carbon\Carbon;
use App\Helpers\GetIP;
use GlobalChargingSequences;
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
    public function getPostedSuppliesByCaseNo(Request $request) 
    {
        try {
            $case_No = $request->query('case_No'); 
    
            $patient_details = PatientRegistry::with('patient_details')
                            ->where('case_No', $case_No)
                            ->first();
    
            if (!$patient_details) {
                return response()->json(['msg' => 'Patient not found'], 404);
            }
    
            $supplies_data = InventoryTransaction::where('patient_Registry_Id', $case_No)
                            ->where('transaction_Acctg_TransType', 'CS')
                            ->orderBy('created_at', 'desc')
                            ->get();

            $supplies_data->each(function ($item) use ($case_No) {
                $item->load(['nurse_logbook' => function ($query) use ($item, $case_No) {
                    $query->where('case_No', $case_No)
                        ->whereNotNull('requestNum')
                        ->where('item_Id', $item->transaction_Item_Id);
                }]);
            });
    
            $response = [
                'patient_details' => [
                    'patient_Id' => $patient_details->patient_Id,
                    'case_No' => $patient_details->case_No,
                    'patient_Name' => $patient_details->patient_details->lastname . ', ' . $patient_details->patient_details->firstname . ' ' . $patient_details->patient_details->middlename,
                    'age' => $patient_details->patient_Age,
                    'sex' => $patient_details->patient_details->sex_id,
                    'birthdate' => $patient_details->patient_details->birthdate,
                    'doctor' => $patient_details->attending_Doctor_fullname,
                    'mscPrice_Schemes' => $patient_details->mscPrice_Schemes,
                    'mscAccount_Trans_Types' => $patient_details->mscAccount_Trans_Types,
                    'discharged_Userid' => $patient_details->discharged_Userid,
                    'discharged_Date' => $patient_details->discharged_Date,
                    'inventory_data' => $supplies_data->toArray(),
                ],
            ];
    
            return response()->json($response, 200);
    
        } catch (\Exception $e) {
            return response()->json(["msg" => $e->getMessage()], 500);
        }
    }
    public function submitManualPostingSupplies(Request $request) 
    {
        DB::connection('sqlsrv_medsys_inventory')->beginTransaction();
        DB::connection('sqlsrv_patient_data')->beginTransaction();
        DB::connection('sqlsrv_mmis')->beginTransaction();
        DB::connection('sqlsrv_medsys_nurse_station')->beginTransaction();

        try {
            if ($this->check_is_allow_medsys) {
                $cashAssessmentSequence = new GlobalChargingSequences();
                $cashAssessmentSequence->incrementSequence();
                $assessnum_sequence = $cashAssessmentSequence->getSequence();
                $assessnum_sequence = $assessnum_sequence['MedSysCashSequence'];
                $inventoryChargeSlip = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->increment('DispensingCSlip');
                $nurseChargeSlip = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->increment('ChargeSlip');
                if ($inventoryChargeSlip && $nurseChargeSlip) {
                    $medSysRequestNum = DB::connection('sqlsrv_medsys_nurse_station')->table('STATION.dbo.tbNursePHSlip')->value('ChargeSlip');
                    $medSysReferenceNum = DB::connection('sqlsrv_medsys_inventory')->table('INVENTORY.dbo.tbInvChargeSlip')->value('DispensingCSlip');
                } else {
                    throw new \Exception('Error in getting MedSys Request Number and Reference Number');
                }
            } else {
                $assessnum_sequence = SystemSequence::where('code', 'GAN')->first();
            }

            $today = Carbon::now();
            $patient_Id = $request->payload['patient_Id'];
            $case_No = $request->payload['caseNo'];
            $patient_Name = $request->payload['patient_Name'];
            $guarantor_Id = $request->payload['guarantor_Id'] ?? $patient_Id;
            $doctor_Id = $request->payload['doctor_id'];
            $account = $request->payload['account'];
            $patient_Type = $request->payload['patient_Type'] == 2 ? 'O' : ($request->payload['patient_Type'] == 5 ? 'E' : 'I');

            if (isset($request->payload['Supplies']) && count($request->payload['Supplies']) > 0) {
                foreach ($request->payload['Supplies'] as $medicine) {
                    $revenueID = $medicine['code'];
                    $warehouseID = $medicine['warehouse_id'];
                    $warehouse_medsysID = $medicine['warehouse_medsys_id'];
                    $item_OnHand = $medicine['item_OnHand'];
                    $itemID = $medicine['map_item_id'];
                    $item_name = $medicine['item_name'];
                    $requestQuantity = $medicine['quantity'];
                    $item_list_cost = $medicine['item_ListCost'];
                    $price = floatval(str_replace([',', '₱'], '', $medicine['price']));
                    $stat = $medicine['stat'] ?? null;
                    $amount = floatval(str_replace([',', '₱'], '', $medicine['amount']));
                    $requestNum = $revenueID . $medSysRequestNum;
                    $referenceNum = 'C' . $medSysReferenceNum . 'M';
                    $refNumSequence = $revenueID . $medSysReferenceNum;

                    if (($patient_Type == 'O' || $patient_Type == 'E') && $account == 'Self-Pay') {
                        throw new \Exception('Self-Pay Patients are not allowed for manual posting');
                    } else if (($patient_Type == 'O' || $patient_Type == 'E') && $account == 'Company / Insurance') {
                        HISBillingOut::create([
                            'patient_Id'            => $patient_Id,
                            'case_No'               => $case_No,
                            'patient_Type'          => $patient_Type,
                            'transDate'             => $today,
                            'msc_price_scheme_id'   => 2,
                            'revenueID'             => $revenueID,
                            'itemID'                => $itemID,
                            'quantity'              => $requestQuantity,
                            'refNum'                => $requestNum,
                            'ChargeSlip'            => $requestNum,
                            'amount'                => $amount,
                            'userId'                => Auth()->user()->idnumber,
                            'request_doctors_id'    => $doctor_Id,
                            'net_amount'            => $amount,
                            'hostName'              => (new GetIP())->getHostname(),
                            'accountnum'            => $guarantor_Id,
                            'auto_discount'         => 0,
                            'createdby'             => Auth()->user()->idnumber,
                            'created_at'            => $today,
                        ]);
                        NurseLogBook::create([
                            'branch_Id'             => 1,
                            'patient_Id'            => $patient_Id,
                            'case_No'               => $case_No,
                            'patient_Name'          => $patient_Name,
                            'patient_Type'          => $patient_Type,
                            'revenue_Id'            => $revenueID,
                            'requestNum'            => $requestNum,
                            'referenceNum'          => $referenceNum,
                            'item_Id'               => $itemID,
                            'description'           => $item_name,
                            'Quantity'              => $requestQuantity,
                            'item_OnHand'           => $item_OnHand,
                            'item_ListCost'         => $item_list_cost,
                            'section_Id'            => $warehouseID,
                            'price'                 => $price,
                            'amount'                => $amount,
                            'record_Status'         => 'W',
                            'user_Id'               => Auth()->user()->idnumber,
                            'request_Date'          => $today,
                            'process_By'            => Auth()->user()->idnumber,
                            'process_Date'          => $today,
                            'stat'                  => $stat,
                            'ismedicine'            => 1,
                            'createdat'             => $today,
                            'createdby'             => Auth()->user()->idnumber,
                        ]);
                        NurseCommunicationFile::create([
                            'branch_Id'             => 1,
                            'patient_Id'            => $patient_Id,
                            'case_No'               => $case_No,
                            'patient_Name'          => $patient_Name,
                            'patient_Type'          => $patient_Type,
                            'item_Id'               => $itemID,
                            'amount'                => $amount,
                            'quantity'              => $requestQuantity,
                            'section_Id'            => $warehouseID,
                            'request_Date'          => $today,
                            'revenue_Id'            => $revenueID,
                            'record_Status'         => 'W',
                            'requestNum'            => $requestNum,
                            'referenceNum'          => $referenceNum,
                            'stat'                  => $stat,
                            'createdby'             => Auth()->user()->idnumber,
                            'createdat'             => $today,
                        ]);
                        InventoryTransaction::create([
                            'branch_Id'                             => 1,
                            'warehouse_Id'                          => $warehouseID,
                            'patient_Id'                            => $patient_Id,
                            'patient_Registry_Id'                   => $case_No,
                            'transaction_Item_Id'                   => $itemID,
                            'transaction_Date'                      => $today,
                            'trasanction_Reference_Number'          => $referenceNum,
                            'transaction_Acctg_TransType'           => $revenueID,
                            'transaction_Qty'                       => $requestQuantity,
                            'transaction_Item_OnHand'               => $item_OnHand,
                            'transaction_Item_ListCost'             => $item_list_cost,
                            'transaction_Item_SellingAmount'        => $price,
                            'transaction_Item_TotalAmount'          => $amount,
                            'transaction_UserID'                    => Auth()->user()->idnumber,
                            'created_at'                            => $today,
                            'createdBy'                             => Auth()->user()->idnumber,
                        ]);
                        if ($this->check_is_allow_medsys):
                            MedSysDailyOut::create([
                                'Hospnum'               => $patient_Id,
                                'IDNum'                 => $case_No . 'B',
                                'TransDate'             => $today,
                                'RevenueID'             => $revenueID,
                                'ItemID'                => $itemID,
                                'Quantity'              => $requestQuantity,
                                'RefNum'                => $requestNum,
                                'ChargeSlip'            => $requestNum,
                                'Amount'                => $amount,
                                'UserID'                => Auth()->user()->idnumber,
                                'HostName'              => (new GetIP())->getHostname(),
                                'AutoDiscount'          => 0,
                            ]);
                            tbNurseLogBook::create([
                                'Hospnum'               => $patient_Id,
                                'IDnum'                 => $case_No . 'B',
                                'PatientType'           => $patient_Type,
                                'RevenueID'             => $revenueID,
                                'RequestDate'           => $today,
                                'ItemID'                => $itemID,
                                'Description'           => $item_name,
                                'Quantity'              => $requestQuantity,
                                'SectionID'             => $warehouseID,
                                'Amount'                => $amount,
                                'RecordStatus'          => 'W',
                                'UserID'                => Auth()->user()->idnumber,
                                'ProcessBy'             => Auth()->user()->idnumber,
                                'ProcessDate'           => $today,
                                'RequestNum'            => $requestNum,
                                'ReferenceNum'          => $referenceNum,
                                'Stat'                  => $stat,
                            ]);
                            tbNurseCommunicationFile::create([
                                'Hospnum'               => $patient_Id,
                                'IDnum'                 => $case_No . 'B',
                                'PatientType'           => $patient_Type,
                                'ItemID'                => $itemID,
                                'Amount'                => $amount,
                                'Quantity'              => $requestQuantity,
                                'SectionID'             => $warehouseID,
                                'RequestDate'           => $today,
                                'RevenueID'             => $revenueID,
                                'RecordStatus'          => 'W',
                                'UserID'                => Auth()->user()->idnumber,
                                'RequestNum'            => $requestNum,
                                'ReferenceNum'          => $referenceNum,
                                'Stat'                  => $stat,
                            ]);
                            tbInvStockCard::create([
                                'SummaryCode'           => $revenueID,
                                'Hospnum'               => $patient_Id,
                                'IdNum'                 => $case_No . 'B',
                                'TransDate'             => $today,
                                'RevenueID'             => $revenueID,
                                'RefNum'                => $refNumSequence,
                                'Quantity'              => $requestQuantity,
                                'Balance'               => $item_OnHand,
                                'NetCost'               => $item_list_cost,
                                'Amount'                => $amount,
                                'UserID'                => Auth()->user()->idnumber,
                                'RequestByID'           => Auth()->user()->idnumber,
                                'LocationID'            => $warehouse_medsysID,
                            ]);
                        endif;
                    } else if ($patient_Type == 'I' && $account == 'Company / Insurance') {
                        // Submit Datas for Inpatient ( Insurance tagged )
                    } else if ($patient_Type == 'I' && $account == 'Self-Pay') {
                        // Submit Datas for Inpatient ( Self-Pay tagged )
                    }

                }
            }
            DB::connection('sqlsrv_medsys_inventory')->commit();
            DB::connection('sqlsrv_patient_data')->commit();
            DB::connection('sqlsrv_mmis')->commit();
            DB::connection('sqlsrv_medsys_nurse_station')->commit();
            return response()->json(['message' => 'Medicine Posted Successfully'], 200);

        } catch(\Exception $e) {
            DB::connection('sqlsrv_medsys_inventory')->rollBack();
            DB::connection('sqlsrv_patient_data')->rollBack();
            DB::connection('sqlsrv_mmis')->rollBack();
            DB::connection('sqlsrv_medsys_nurse_station')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
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
                            'requestNum'        => $requestNum,
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
                            'requestNum'        => $requestNum,
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
                                    'RequestNum'        => $requestNum,
                                    'RecordStatus'      => 'R',
                                ]);

                            tbNurseCommunicationFile::where('Hospnum', $patient_Id)
                                ->where('IDnum', $case_No . 'B')
                                ->where('RequestNum', $requestNum)
                                ->where('ItemID', $item_Id)
                                ->update([
                                    'Remarks'           => $remarks,
                                    'RequestNum'        => $requestNum,
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
    public function postReturnSupplies(Request $request) 
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
            $patient_Type = $request->payload['patient_Type'] == 2 ? 'O' : ($request->payload['patient_Type'] == 5 ? 'E' : 'I');

            if (isset($request->payload['Items']) && count($request->payload['Items']) > 0) {
                foreach ($request->payload['Items'] as $items) {
                    if (isset($items['nurse_logbook'])) {
                        $description = $items['nurse_logbook']['description'];
                    }
                    $referenceNum = $items['trasanction_Reference_Number'];
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
                        'referenceNum'          => $referenceNum,
                        'item_Id'               => $item_Id,
                        'description'           => $description,
                        'Quantity'              => $Quantity_To_return * -1,
                        'item_ListCost'         => $list_cost,
                        'price'                 => $price,
                        'amount'                => $return_total_amount * -1,
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
                        'remarks'               => $remarks,
                        'user_Id'               => Auth()->user()->idnumber,
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
                            'PatientType'               => $patient_Type == 'E' ? 'O' : $patient_Type,
                            'RevenueID'                 => $revenue_Id,
                            'RequestDate'               => $today,
                            'ItemID'                    => $item_Id,
                            'Description'               => $description,
                            'Quantity'                  => $Quantity_To_return * -1,
                            'Amount'                    => $return_total_amount * -1,
                            'SectionID'                 => $warehouse_Id,
                            'UserID'                    => Auth()->user()->idnumber,
                            'ProcessBy'                 => Auth()->user()->idnumber,
                            'ProcessDate'               => $today,
                            'Remarks'                   => $remarks,
                            'ReferenceNum'              => $referenceNum,
                        ]);
                        tbNurseCommunicationFile::create([
                            'Hospnum'                   => $patient_Id,
                            'IDnum'                     => $case_No . 'B',
                            'PatientType'               => $patient_Type == 'E' ? 'O' : $patient_Type,
                            'ItemID'                    => $item_Id,
                            'Amount'                    => $return_total_amount * -1,
                            'Quantity'                  => $Quantity_To_return * -1,
                            'SectionID'                 => $warehouse_Id,
                            'RequestDate'               => $today,
                            'RevenueID'                 => $revenue_Id,
                            'UserID'                    => Auth()->user()->idnumber,
                            'ReferenceNum'              => $referenceNum,
                            'Remarks'                   => $remarks,
                        ]);
                        tbInvStockCard::create([
                            'SummaryCode'               => 'CS',
                            'HospNum'                   => $patient_Id,
                            'IdNum'                     => $case_No . 'B',
                            'ItemID'                    => $item_Id,
                            'TransDate'                 => $today,
                            'RevenueID'                 => 'CS',
                            'RefNum'                    => $referenceNum,
                            'Quantity'                  => $Quantity_To_return * -1,
                            'NetCost'                   => $list_cost,
                            'Amount'                    => $return_total_amount * -1,
                            'UserID'                    => Auth()->user()->idnumber,
                            'LocationID'                => 21,
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
