<?php

namespace App\Http\Controllers\AppointmentController;

use Illuminate\Support\Str;

use App\Helpers\Appointment\AppointmentHelper;
use App\Helpers\GetIP;
use App\Http\Controllers\Controller;
use App\Models\Appointments\PatientAppointmentsTemporary;
use App\Models\Appointments\UserAppointments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = UserAppointments::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $token = $user->api_token;

            if (!$token) {
                $token = Str::random(60);
                $user->update(['api_token' => $token]);
            }
            return response()->json([
                'user' => $user, 'access_token' => $token, 'message' => 'Login successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(),'message' => 'Login unsucessfully'], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = UserAppointments::where('api_token', $request->bearerToken())->first();

        if ($user) {
            $user->update(['api_token' => null]);
            return response()->json(['message' => 'Logged out successfully'],201);
        }

        return response()->json(['message' => 'Invalid token'], 500);
    }


    public function refreshToken(Request $request)
    {
        $patient = $request->patient;
        $patient->revokeToken();
        $newAccessToken = $patient->createToken();
        return response()->json(['access_token' => $newAccessToken], 200);
    }
    
}
