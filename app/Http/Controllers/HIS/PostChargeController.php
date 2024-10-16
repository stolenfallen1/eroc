<?php

namespace App\Http\Controllers\HIS;

use DB;
use Carbon\Carbon;
use App\Helpers\GetIP;
use App\Helpers\HIS\Patient;
use Illuminate\Http\Request;
use App\Helpers\HIS\SeriesNo;
use App\Models\HIS\BillingOutModel;
use App\Http\Controllers\Controller;
use App\Models\HIS\HemodialysisMonitoringModel;

class PostChargeController extends Controller
{
    public function getcharges(Request $request){
        $data = BillingOutModel::where("case_no",Request()->case_no)->get();
        return response()->json($data,200);
        
    }

    public function postcharge(Request $request)
    {

        // DB::connection('sqlsrv')->beginTransaction();
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_hemodialysis')->beginTransaction();

        $getchargeslip = DB::connection('sqlsrv_medsys_hemodialysis')->table('tbKidneyChargeSlip')->select('ChargeSlip')->first();
        DB::connection('sqlsrv_medsys_hemodialysis')->table('tbKidneyChargeSlip')->increment('ChargeSlip');
        $ChargeSlip = 'C' . $getchargeslip->ChargeSlip . 'K';

        try {
            // DB::connection('sqlsrv_medsys_hemodialysis')->table('tbkidneyChargePrint')->delete();
            if(count($request->items) > 0){
                foreach($request->items as $row) {
                    $AccountNum = $request->patient_info['AccountNum'];
                    $patientType = $request->patient_info['patientType'];

                    $Hospnum = $request->patient_info['HospNum'];
                    $IDNum = $request->patient_info['IDNum'];
                    $Item = $row['itemcode'];
                    $Amount = $row['totalamount'];
                    $Quantity = $row['qty'];
                    $RequestDate = Carbon::now();
                    $RevenueID = $row['revenue'];
                    $RecordStatus = 'X';
                    $UserID = Auth()->user()->idnumber;
                    $Description = $row['itemdescription'];
                    $PatientType = 'O';
                    $DrCr = 'D';
                    $RoomID = 'OPD';
                    $Department = '';
                    $DoctorID = $request->doctor_info['doctor_code'];
                
                    $ARCode = '';
                    $RequestNum = '';
                    $RequestDocName = $request->doctor_info['doctor_name'];
                    $PackageID = '';
                    
                    $set_package = 0;
                    // NEW CDG Billing DATABASE
                    if($Item === '9343'){
                        $set_package = 1;
                    }else if($Item === '9342') {
                        $set_package = 1;
                    }

                    if($set_package == 0){

                        BillingOutModel::create([
                            'pid' => $Hospnum,
                            'case_no' => $IDNum,
                            'transDate' => $RequestDate,
                            'revenue_id' => $RevenueID,
                            'item_id' => $Item,
                            'quantity' => $Quantity,
                            'refnum' => $ChargeSlip,
                            'amount' => $Amount,
                            'userid' => $UserID,
                            'drcr' => $DrCr,
                            'request_doctors_id' => $DoctorID,
                            'room_id' => $RoomID,
                            'net_amount' => $Amount,
                            'HostName' => (new GetIP())->getHostname(),
                            'accountnum' => $AccountNum,
                            'patient_type' => $PatientType,
                            'auto_discount' => ''
                        ]);
                        // HEMODIAYLYSIS DATABASE
                        DB::connection('sqlsrv_medsys_hemodialysis')->statement("SET NOCOUNT ON;EXEC spKidney_Append_Charging ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?",
                            [
                                $Hospnum,
                                $IDNum,
                                $Item,
                                $Amount,
                                $Quantity,
                                $RequestDate,
                                $RevenueID,
                                $RecordStatus,
                                $UserID,
                                $Description,
                                $PatientType,
                                $DrCr,
                                $RoomID,
                                $Department,
                                $DoctorID,
                                $ChargeSlip,
                                $ARCode,
                                $RequestNum,
                                $RequestDocName,
                                $PackageID
                            ]
                        );
                    }

                    if($request->patient_info['register_source_case_no']){
                        // DB::connection('sqlsrv_medsys_hemodialysis')->statement("SET NOCOUNT ON;EXEC spKidney_Append_Charging ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?",
                        // [
                        //     $Hospnum,
                        //     $request->patient_info['register_source_case_no'],
                        //     $Item,
                        //     $Amount,
                        //     $Quantity,
                        //     $RequestDate,
                        //     $RevenueID,
                        //     $RecordStatus,
                        //     $UserID,
                        //     $Description,
                        //     'I',
                        //     $DrCr,
                        //     $RoomID,
                        //     $Department,
                        //     $DoctorID,
                        //     $ChargeSlip,
                        //     $ARCode,
                        //     $RequestNum,
                        //     $RequestDocName,
                        //     $PackageID
                        // ]);
                       if($set_package == 0){
                            HemodialysisMonitoringModel::create([
                                'pid'=>$Hospnum,
                                'case_no_out'=>$request->patient_info['IDNum'],
                                'case_no_in'=>$request->patient_info['register_source_case_no'],
                                'transdate'=>$RequestDate,
                                'revenue_id'=>$RevenueID,
                                'refnum'=>$ChargeSlip,
                                'item_id'=>$Item,
                                'qty'=>$Quantity,
                                'amount'=>$Amount,
                                'user_id'=>$UserID,
                            ]);
                        }
                    }

                    // DB::connection('sqlsrv_medsys_hemodialysis')->statement("SET NOCOUNT ON;EXEC spKidney_SaveChargePrint ?,?,?,?,?,?,?,?",
                    //     [
                    //         $Item,
                    //         $Amount,
                    //         $Quantity,
                    //         $RevenueID,
                    //         $Description,
                    //         '',
                    //         '',
                    //         ''
                    //     ]
                    // );
                        
                }
            }
            
            if(count($request->outpatient_charges) > 0){
                foreach($request->outpatient_charges as $row) {
                    $AccountNum = $request->patient_info['AccountNum'];
                    $patientType = $request->patient_info['patientType'];

                    $Hospnum = $request->patient_info['HospNum'];
                    $IDNum = $request->patient_info['IDNum'];
                    $Item = $row['itemcode'];
                    $Amount = $row['totalamount'];
                    $Quantity = $row['qty'];
                    $RequestDate = Carbon::now();
                    $RevenueID = $row['revenue'];
                    $RecordStatus = 'X';
                    $UserID = Auth()->user()->idnumber;
                    $Description = $row['itemdescription'];
                    $PatientType = 'O';
                    $DrCr = 'D';
                    $RoomID = 'OPD';
                    $Department = '';
                    $DoctorID = $request->doctor_info['doctor_code'];

                    $ARCode = '';
                    $RequestNum = '';
                    $RequestDocName = $request->doctor_info['doctor_name'];
                    $PackageID = '';

                    // NEW CDG Billing DATABASE
                    BillingOutModel::create([
                        'pid' => $Hospnum,
                        'case_no' => $IDNum,
                        'transDate' => $RequestDate,
                        'revenue_id' => $RevenueID,
                        'item_id' => $Item,
                        'quantity' => $Quantity,
                        'refnum' => $ChargeSlip,
                        'amount' => $Amount,
                        'userid' => $UserID,
                        'drcr' => $DrCr,
                        'request_doctors_id' => $DoctorID,
                        'room_id' => $RoomID,
                        'net_amount' => $Amount,
                        'HostName' => (new GetIP())->getHostname(),
                        'accountnum' => $AccountNum,
                        'patient_type' => $PatientType,
                        'auto_discount' => ''
                    ]);

                    // HEMODIAYLYSIS DATABASE
                    DB::connection('sqlsrv_medsys_hemodialysis')->statement(
                        "SET NOCOUNT ON;EXEC spKidney_Append_Charging ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?",
                        [
                            $Hospnum,
                            $IDNum,
                            $Item,
                            $Amount,
                            $Quantity,
                            $RequestDate,
                            $RevenueID,
                            $RecordStatus,
                            $UserID,
                            $Description,
                            $PatientType,
                            $DrCr,
                            $RoomID,
                            $Department,
                            $DoctorID,
                            $ChargeSlip,
                            $ARCode,
                            $RequestNum,
                            $RequestDocName,
                            $PackageID
                        ]
                    );

                
                    HemodialysisMonitoringModel::create([
                        'pid' => $Hospnum,
                        'case_no_out' => $request->patient_info['IDNum'],
                        'case_no_in' => $request->patient_info['register_source_case_no'],
                        'transdate' => $RequestDate,
                        'revenue_id' => $RevenueID,
                        'refnum'=>$ChargeSlip,
                        'item_id' => $Item,
                        'qty' => $Quantity,
                        'amount' => $Amount,
                        'user_id' => $UserID,
                    ]);
                }
            }
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_hemodialysis')->commit();
            $data = BillingOutModel::where("case_no", $request->patient_info['IDNum'])->where("refnum", $ChargeSlip)->get();
            return response()->json($data, 200);

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollback();
            DB::connection('sqlsrv_medsys_hemodialysis')->rollback();
            return response()->json(["message" => $e->getMessage()], 200);
        }
    }


    public function cancelcharges(Request $request){
        DB::connection('sqlsrv_billingOut')->beginTransaction();
        DB::connection('sqlsrv_medsys_hemodialysis')->beginTransaction();

        try {
            foreach($request->items as $row) {
                $DoctorID = $row['request_doctors_id'];
                $AccountNum = $row['accountnum'];
                $Hospnum = $row['pid'];
                $IDNum = $row['case_no'];
                $Item = $row['item_id'];
                $Amount = $row['amount'];
                $Quantity = $row['quantity'];
                $RequestDate = Carbon::now();
                $UserID = Auth()->user()->idnumber;
                $PatientType = $row['patient_type'];
                $DrCr = $row['drcr'];
                $RoomID = $row['room_id'];
                $RefNum = $row['refnum'];
                $ARCode = $row['arcode'];
                $RevenueID = $row['revenue_id'];
                
                BillingOutModel::create([
                    'pid' => $Hospnum,
                    'case_no' => $IDNum,
                    'transDate' => $RequestDate,
                    'revenue_id' => $RevenueID,
                    'item_id' => $Item,
                    'quantity' => $Quantity,
                    'refnum' => $RefNum,
                    'amount' => -$Amount,
                    'userid' => $UserID,
                    'drcr' => $DrCr,
                    'request_doctors_id' => $DoctorID,
                    'room_id' => $RoomID,
                    'net_amount' => $Amount,
                    'HostName' => (new GetIP())->getHostname(),
                    'accountnum' => $AccountNum,
                    'patient_type' => $PatientType,
                    'auto_discount' => ''
                ]);

                DB::connection('sqlsrv_medsys_hemodialysis')->statment("SET NOCOUNT ON;EXEC spKidney_UpdateCharging ?,?,?,?,?,?,?,?,?,?,?,?,?",
                    [
                        $Hospnum,
                        $IDNum,
                        $Item,
                        $Amount,
                        $Quantity,
                        $RequestDate,
                        $RevenueID,
                        $UserID,
                        $PatientType,
                        'C',
                        $RoomID,
                        $RefNum,
                        $ARCode
                    ]
                );
            }
            
            DB::connection('sqlsrv_billingOut')->commit();
            DB::connection('sqlsrv_medsys_hemodialysis')->commit();

        } catch (\Exception $e) {
            DB::connection('sqlsrv_billingOut')->rollback();
            DB::connection('sqlsrv_medsys_hemodialysis')->rollback();
            return response()->json(["message" => $e->getMessage()], 200);
        }
    }
}
