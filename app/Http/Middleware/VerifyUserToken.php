<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Appointments\UserAppointments;

class VerifyUserToken
{
    /**
     * Handle an incoming request and validate the API token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Unauthorized: No token provided'], 401);
        }
        $user = UserAppointments::where('api_token', $token)->first();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized: Invalid token'], 401);
        }
        $request->merge(['id' => $user->id, 'api_token' => $user->api_token]);
        $response = $next($request);
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            $data['api_token'] = $user->api_token;
            $response->setData($data);
        }


        return $response;
    }
}
