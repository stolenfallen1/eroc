<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Appointments\AppointmentUser;
class AuthAppointmentController extends Controller
{

    public function login(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'portal_UID' => 'required',
            'portal_PWD' => 'required',
        ]);

        // Find the patient login record
        $patientLogin = AppointmentUser::where('portal_UID', $validated['portal_UID'])->first();

        // Check credentials
        if (!$patientLogin || !Hash::check($validated['portal_PWD'], $patientLogin->portal_PWD)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate token
        $token = $patientLogin->createToken();

        // Return patient data and token
        return response()->json(['user' => $patientLogin, 'access_token' => $token], 200);
    }
    public function getdetails(Request $request)
    {
        // Assuming you want to return the patient data
        return response()->json(['user' => $request->patient], 200);
    }

    public function logout(Request $request)
    {
        // Retrieve the patient from the request
        $patient = $request->patient;

        // Revoke the current access token
        $patient->revokeToken();

        return response()->json(['message' => 'success'], 200);
    }

    public function refreshToken(Request $request)
    {
        // Retrieve the patient from the request
        $patient = $request->patient;

        // Revoke the current access token
        $patient->revokeToken();

        // Generate a new access token
        $newAccessToken = $patient->createToken();


        return response()->json(['access_token' => $newAccessToken], 200);
    }
    
}
