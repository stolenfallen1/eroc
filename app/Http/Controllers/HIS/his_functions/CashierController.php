<?php

namespace App\Http\Controllers\HIS\his_functions;

use App\Http\Controllers\Controller;
use App\Helpers\GetIP;
use App\Models\HIS\BillingOutModel;
use App\Models\HIS\his_functions\CashAssessment;
use App\Models\HIS\his_functions\CashORMaster;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    //
    public function populatechargeitem(Request $request) 
    {
        DB::beginTransaction();
        try {
            $refNum = $request->query('refNum'); 

            $data = CashAssessment::with('items', 'doctor_details')
                ->where('refNum', $refNum)
                ->get();
            
            DB::commit();
            return response()->json(['data' => $data], 200);

        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function save(Request $request) 
    {
        DB::beginTransaction();
        try {
            $patient_id = $request->payload['patient_id'];
            $case_no = $request->payload['register_id_no'];
            $transDate = Carbon::now()->format('Y-m-d');
            $revenue_id = $request->payload['transaction_code'];
            $item_id = $request->payload['itemID'];
            $refNum = $request->payload['refNum'];
            $ORNum = $request->payload['ORNumber'];
            $amount = floatval(str_replace([',', '₱'], '', ['amount']));

            $update = CashAssessment::where('patient_id' , $patient_id)
                ->where('register_id_no', $case_no)
                ->where('revenueID', $revenue_id)
                ->where('itemID', $item_id)
                ->update([
                    'ORNumber' => $ORNum, 
                    'updatedBy' => Auth()->user()->idnumber, 
                    'updated_at' => Carbon::now()
                ]);
            
            if ($update) {
                BillingOutModel::create([
                    'pid' => $patient_id,
                    'case_no' => 'CASH',
                    'transDate' => $transDate,
                    'revenue_id' => $revenue_id,
                    'drcr' => 'D',
                    'item_id' => $item_id,
                    'quantity' => 1,
                    'refnum' => $ORNum,
                    'amount' => $amount,
                    'userid' => Auth()->user()->idnumber,
                    'net_amount' => $amount,
                    'HostName' => (new GetIP())->getHostname(),
                    'accountnum' => $patient_id,
                    'ChargeSlip' => $refNum,
                    'auto_discount' => 0,
                    'patient_type' => 0,
                ]);
                DB::commit();
                return response()->json(['message' => 'Payment saved successfully']);
            } else {
                return response()->json(['message' => 'Failed to save payment'], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function getOR(Request $request) 
    {
        DB::beginTransaction();
        try {
            $orNum = $request->query('RefNum'); 

            $data = CashORMaster::where('RefNum', $orNum)->get();
            DB::commit();
            return response()->json(['data' => $data], 200);

        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelOR(Request $request) 
    {
        DB::beginTransaction();
        try {


        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
