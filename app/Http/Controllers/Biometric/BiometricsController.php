<?php

namespace App\Http\Controllers\Biometric;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Biometric\Biometrics;
use Carbon\Carbon;

class BiometricsController extends Controller
{   
    
    protected $p_BranchId;
    protected $p_Empnum;
    protected $p_TransDate;
    protected $p_TransType;
    protected $p_TransDateButtonPress;
    protected $p_finger;
    protected $p_hardwareid;
    protected $p_Hash;
    protected $p_hostname;
    protected $p_token;
    protected $p_dbstatus; 
       
    public function __construct()
    {
        $this->p_BranchId             = Request()->p_BranchId ?? "";
        $this->p_Empnum               = Request()->p_Empnum ?? "";
        $this->p_TransDate            = Request()->p_TransDate ?? "";
        $this->p_TransDateButtonPress = Request()->p_TransDateButtonPress ?? "";
        $this->p_TransType            = Request()->p_TransType ?? "";
        $this->p_finger               = Request()->p_finger ?? "";
        $this->p_hardwareid           = Request()->p_hardwareid ?? "";
        $this->p_Hash                 = Request()->p_Hash ?? "";
        $this->p_hostname             = Request()->p_hostname ?? "";
        $this->p_dbstatus             = Request()->p_dbstatus ?? "";
        $this->p_token                = Request()->p_token ?? "";
    }
    
    public function index(){

        $data = Biometrics::where('branch_id',$this->p_BranchId)
                          ->where('Empnum',$this->p_Empnum)
                          ->whereDate('TransDate',Carbon::parse($this->p_TransDate)->format('Y-m-d'))
                          ->where('TransType',$this->p_TransType)
                          ->get();

        return response()->json($data,200);
     
    }
    public function store(Request $request)
    {

        DB::connection('sqlsrv_cdh_payroll')->beginTransaction();
        try {
            DB::connection('sqlsrv_cdh_payroll')->statement(
                "SET NOCOUNT ON; EXEC spBiometric_SaveHandPunch_Online ?, ?, ?, ?, ?, ?, ?, ?, ?, ?",
                [
                    $this->p_BranchId,
                    $this->p_Empnum,
                    $this->p_TransDate,
                    $this->p_TransDateButtonPress,
                    $this->p_TransType,
                    $this->p_finger,
                    $this->p_hardwareid,
                    $this->p_Hash,
                    $this->p_hostname,
                    $this->p_dbstatus,
                    $this->p_token
                ]
            );

            $data['p_BranchId']             = $this->p_BranchId ?? "";
            $data['p_Empnum']               = $this->p_Empnum ?? "";
            $data['p_TransDate']            = $this->p_TransDate ?? "";
            $data['p_TransType']            = $this->p_TransType ?? "";
            $data['p_finger']               = $this->p_finger ?? "";
            DB::connection('sqlsrv_cdh_payroll')->commit();
            return response()->json(['message' => 'success','data'=>$data], 201);
        } catch (\Exception $e) {

            DB::connection('sqlsrv_cdh_payroll')->rollback();
            Log::error('Error transaction:', [
                'error_message' => $e->getMessage(),   // The error message
                'error_code'    => $e->getCode(),      // The error code
                'file'          => $e->getFile(),      // The file in which the error occurred
                'line'          => $e->getLine(),      // The line number where the error occurred
                'trace'         => $e->getTraceAsString()  // The stack trace
            ]);
            return response()->json(["error" => $e->getMessage()], 200);
        }
    }
}
