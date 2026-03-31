<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtAuthService
{
    /**
     * Generate JWT token for user.
     *
     * @param User $user
     * @return string|null
     */
    public function generateToken(User $user): ?string
    {
        try {
            $token = JWTAuth::fromUser($user);
            return $token;
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Attempt to authenticate user with credentials.
     *
     * @param array $credentials
     * @return string|null
     */
    public function attemptLogin(array $credentials): ?string
    {
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return null;
            }
            return $token;
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Refresh the current token.
     *
     * @return string|null
     */
    public function refreshToken(): ?string
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return $token;
        } catch (TokenExpiredException $e) {
            return null;
        } catch (TokenInvalidException $e) {
            return null;
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Invalidate the current token (logout).
     *
     * @return bool
     */
    public function invalidateToken(): bool
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Get the authenticated user from token.
     *
     * @return User|null
     */
    public function getUserFromToken(): ?User
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return null;
            }
            return $user;
        } catch (TokenExpiredException $e) {
            return null;
        } catch (TokenInvalidException $e) {
            return null;
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Validate token.
     *
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        try {
            return JWTAuth::parseToken($token)->check();
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Check if token is expired.
     *
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        try {
            JWTAuth::parseToken()->checkExpiration();
            return false;
        } catch (TokenExpiredException $e) {
            return true;
        } catch (JWTException $e) {
            return true;
        }
    }

    /**
     * Get token expiration time in minutes.
     *
     * @return int
     */
    public function getTokenTTL(): int
    {
        return (int) config('jwt.ttl', 60);
    }

    /**
     * Get user with token (for login response).
     *
     * @param User $user
     * @return array
     */
    public function getUserWithToken(User $user): array
    {
        $token = $this->generateToken($user);
        
        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->getTokenTTL() * 60
        ];
    }
}
