<?php

namespace App\Http\Controllers\Biometric;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class BiometricsController extends Controller
{
    public function store(Request $request)
    {

        DB::connection('sqlsrv_cdh_payroll')->beginTransaction();
        try {

            $p_BranchId             = $request->p_BranchId ?? "";
            $p_Empnum               = $request->p_Empnum ?? "";
            $p_TransDate            = $request->p_TransDate ?? "";
            $p_TransDateButtonPress = $request->p_TransDateButtonPress ?? "";
            $p_TransType            = $request->p_TransType ?? "";
            $p_finger               = $request->p_finger ?? "";
            $p_hardwareid           = $request->p_hardwareid ?? "";
            $p_Hash                 = $request->p_Hash ?? "";
            $p_hostname             = $request->p_hostname ?? "";
            $p_dbstatus             = $request->p_dbstatus ?? "";

            DB::connection('sqlsrv_cdh_payroll')->statement(
                "SET NOCOUNT ON; EXEC spBiometric_SaveHandPunch_Online ?, ?, ?, ?, ?, ?, ?, ?, ?, ?",
                [
                    $p_BranchId,
                    $p_Empnum,
                    $p_TransDate,
                    $p_TransDateButtonPress,
                    $p_TransType,
                    $p_finger,
                    $p_hardwareid,
                    $p_Hash,
                    $p_hostname,
                    $p_dbstatus
                ]
            );

            DB::connection('sqlsrv_cdh_payroll')->commit();
            return response()->json(['message' => 'success'], 200);
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
