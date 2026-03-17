<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if (!$token = JWTAuth::getToken()) {
                return response()->json([
                    'message' => 'Token not provided',
                    'error' => 'token_absent',
                ], 401);
            }

            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 401);
            }

            // Set the user on the request so $request->user() works
            $request->setUserResolver(fn () => $user);
            
            // Also set it in the auth manager for the api guard
            auth()->guard('api')->setUser($user);
            // And the default if needed
            auth()->setUser($user);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token has expired',
                'error' => 'token_expired',
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token is invalid',
                'error' => 'token_invalid',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token error: ' . $e->getMessage(),
                'error' => 'token_error',
            ], 401);
        }

        return $next($request);
    }
}
