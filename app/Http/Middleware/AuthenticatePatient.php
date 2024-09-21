<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Appointments\PatientAppointmentsTemporary;
use Illuminate\Support\Facades\Hash;

class AuthenticatePatient
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Validate the request
        $validated = $request->validate([
            'portal_UID' => 'required',
            'portal_PWD' => 'required',
        ]);

        // Find the patient login record
        $patientLogin = PatientAppointmentsTemporary::where('portal_UID', $validated['portal_UID'])->first();

        // Check credentials
        if (!$patientLogin || !Hash::check($validated['portal_PWD'], $patientLogin->portal_PWD)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate token
        $token = $patientLogin->createToken();

        // Add patient data to request for use in controller
        $request->merge([
            'patient' => $patientLogin,
            'access_token' => $token,
        ]);

        return $next($request);
    }
}
