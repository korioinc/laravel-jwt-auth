<?php

namespace Korioinc\JwtAuth;

use Korioinc\JwtAuth\Data\Jwt;
use Korioinc\JwtAuth\Exceptions\InvalidTokenStructure;
use Korioinc\JwtAuth\Exceptions\UnsupportedCryptAlgorithm;
use Korioinc\JwtAuth\Services\AuthService;

final class JwtAuth
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Manually refresh the current JWT token with updated user props
     *
     * @param  bool  $force  Force refresh without checking cache
     * @return string|null The new token or null if refresh failed
     */
    public function refreshToken(bool $force = true): ?string
    {
        // Get current token from request
        $token = request()->bearerToken();
        if (! $token) {
            return null;
        }

        try {
            // Decode the current JWT
            $jwt = Jwt::decode($token);

            // Force refresh with preemptive Flag to get updated props
            $refreshedJwt = $this->authService->handleAutoRefresh($jwt, true, $force);

            if (! $refreshedJwt) {
                return null;
            }

            return $refreshedJwt->encode();
        } catch (InvalidTokenStructure|UnsupportedCryptAlgorithm $e) {
            return null;
        }
    }
}
