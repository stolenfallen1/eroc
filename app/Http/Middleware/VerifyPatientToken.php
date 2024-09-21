<?php

namespace App\Http\Middleware;

use App\Models\Appointments\AppointmentUser;
use Closure;
use App\Models\Appointments\PatientAppointmentsTemporary;

class VerifyPatientToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
          // Retrieve the token from the Authorization header
          $token = $request->bearerToken();
        
          if (!$token) {
              return response()->json(['message' => 'No token provided'], 401);
          }
  
          // Find the patient by token
          $patient = AppointmentUser::where('api_token', $token)->first();
  
          if (!$patient) {
              return response()->json(['message' => 'Invalid token'], 401);
          }
  
          // Add the patient to the request
          $request->merge(['patient' => $patient]);
  
          return $next($request);
    }
}
