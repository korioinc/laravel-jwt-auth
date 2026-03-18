<?php

namespace Korioinc\JwtAuth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Korioinc\JwtAuth\Data\Jwt;
use Korioinc\JwtAuth\Exceptions\ExpiredToken;
use Korioinc\JwtAuth\Exceptions\FutureToken;
use Korioinc\JwtAuth\Exceptions\InvalidSignature;
use Korioinc\JwtAuth\Services\AuthService;

class JwtGuard implements Guard
{
    use GuardHelpers;
    use Macroable;

    public function __construct(
        UserProvider $provider,
        private readonly Request $request,
        private readonly AuthService $authService
    ) {
        $this->provider = $provider;
    }

    /**
     * @throws Exceptions\UnsupportedCryptAlgorithm
     */
    public function user(): ?Authenticatable
    {
        if ($this->hasUser()) {
            return $this->user;
        }

        $token = $this->request->bearerToken();
        if (! $token) {
            return null;
        }

        try {
            $jwt = Jwt::decode($token);
        } catch (Exceptions\InvalidTokenStructure) {
            return null;
        }

        try {
            $this->authService->validateAccessToken($jwt);

            // Handle preemptive refresh if enabled
            if (config('jwt-auth.auto_refresh.enabled', false)) {
                $preemptiveRefresh = (int) config('jwt-auth.auto_refresh.preemptive_refresh', 0);
                if ($preemptiveRefresh > 0) {
                    $currentTime = time();
                    $timeUntilExpiry = $jwt->payload->exp - $currentTime;

                    if ($timeUntilExpiry > 0 && $timeUntilExpiry < $preemptiveRefresh) {
                        $refreshJwt = $this->authService->handleAutoRefresh($jwt, true);
                        if ($refreshJwt) {
                            $jwt = $refreshJwt;
                        }
                    }
                }
            }
        } catch (ExpiredToken|FutureToken|InvalidSignature $e) {
            $autoRefreshSucceeded = false;
            if ($e instanceof ExpiredToken
                && config('jwt-auth.auto_refresh.enabled', false) === true) {
                $refreshJwt = $this->authService->handleAutoRefresh($jwt);
                if ($refreshJwt) {
                    $jwt = $refreshJwt;
                    $autoRefreshSucceeded = true;
                }
            }
            if (! $autoRefreshSucceeded) {
                return null;
            }
        }

        $user = $this->provider->retrieveById($jwt->payload->sub);
        if (! $user) {
            return null;
        }

        $this->setUser($user);

        return $user;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        if ($this->provider->retrieveByCredentials($credentials)) {
            return true;
        }

        return false;
    }
}
