<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
//use App\Models\ApiToken;
use Exception;

class AuthToken
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
        
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                //return response()->json(['user_not_found'], 404);
                return response()->json(["ResponseStatus" => "Unsuccessful", "message" => "Invalid or expired or missing token", "ResponseCode" => 401, "ResponseMessage" => $e], 401);
            }
        } catch (Exception $e) {
         return response()->json(["ResponseStatus" => "Unsuccessful", "message" => "Invalid or expired or no token", "ResponseCode" => 500, "ResponseMessage" => $e], 500);
        }

        // if ($user->customerID != $request->header('customerID')) {
        // 	return response()->json(["ResponseStatus" => "Unsuccessful", "message" => "token does not belong to this customer", "ResponseCode" => 401], 401);
        // }
        
        // if (!is_null($request['merchantID'])) {
        //     if ($user->customerID != $request['merchantID']) {
        //         return response()->json(["ResponseStatus" => "Unsuccessful", "message" => "token does not belong to this merchant", "ResponseCode" => 401], 401);
        //     }
        // }
        

        return $next($request);
        //header = {Authorization: Bearer {yourtokenhere}, customerID: customerID}
    }
}