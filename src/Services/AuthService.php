<?php

namespace Korioinc\JwtAuth\Services;

use Illuminate\Support\Str;
use Korioinc\JwtAuth\Data\Jwt;
use Korioinc\JwtAuth\Data\ResponseTokenData;
use Korioinc\JwtAuth\Exceptions\ExpiredToken;
use Korioinc\JwtAuth\Exceptions\FutureToken;
use Korioinc\JwtAuth\Exceptions\InvalidRefreshToken;
use Korioinc\JwtAuth\Exceptions\InvalidSignature;
use Korioinc\JwtAuth\Interfaces\JwtUserInterface;
use Korioinc\JwtAuth\Interfaces\RefreshTokenStorageInterface;
use Korioinc\JwtAuth\Utils\Crypto;
use Korioinc\JwtAuth\Utils\UserUtil;

final readonly class AuthService
{
    public function __construct(
        private RefreshTokenStorageInterface $storage,
        private Crypto $crypto
    ) {}

    public function generateAccessToken(JwtUserInterface $user, Jwt $token, int $lifetime): ResponseTokenData
    {
        $refreshToken = Str::random(128);
        $refreshTokenLifetime = (int) config('jwt-auth.refresh_token.lifetime', 14400);
        $refreshTokenExpiresAt = $token->payload->exp + $refreshTokenLifetime;

        $this->storage->create(
            user: $user,
            refreshToken: $refreshToken,
            expiresAt: $refreshTokenExpiresAt,
            lifetime: $lifetime
        );

        return new ResponseTokenData(
            accessToken: $token->encode(),
            expiresAt: $token->payload->exp,
            refreshToken: $refreshToken,
        );
    }

    /**
     * @throws InvalidRefreshToken
     */
    public function refreshAccessToken(string $refreshToken): ResponseTokenData
    {
        $data = $this->storage->getData($refreshToken);
        if (! $data) {
            throw new InvalidRefreshToken;
        }

        $user = $this->storage->getUser($refreshToken);
        if (! $user) {
            throw new InvalidRefreshToken;
        }

        $this->storage->delete(user: $user, refreshToken: $refreshToken);

        return $user->generateJwt(lifetime: $data->lifetime);
    }

    /**
     * @throws InvalidSignature
     * @throws ExpiredToken
     * @throws FutureToken
     */
    public function validateAccessToken(Jwt $token): void
    {
        // Verify signature
        $this->crypto->verify(
            algorithm: $token->header->algorithm,
            payload: $token->header->getSource().'.'.$token->payload->getSource(),
            src: $token->getCrc()
        );

        $currentTime = time();

        // Check not-before time
        if ($token->payload->nbf !== null && $currentTime < $token->payload->nbf) {
            throw new FutureToken([
                'nbf' => $token->payload->nbf,
                'current_time' => $currentTime,
            ]);
        }

        // Check expiration time
        if ($currentTime > $token->payload->exp) {
            throw new ExpiredToken([
                'exp' => $token->payload->exp,
                'current_time' => $currentTime,
            ]);
        }
    }

    public function handleAutoRefresh(Jwt $jwt, bool $isPreemptive = false, bool $force = false): ?Jwt
    {
        // Check if auto-refreshed token exists in cache (skip if forced)
        if (! $force) {
            $autoRefreshedToken = $this->storage->getAutoRefreshedToken($jwt);
            if ($autoRefreshedToken) {
                request()->attributes->set('jwt_refreshed_token', $autoRefreshedToken);

                return Jwt::decode($autoRefreshedToken);
            }
        }

        // Check grace Period after expiration && Check if auto-refresh has already been processed (skip if forced)
        if (! $force && (time() - $jwt->payload->exp > config('jwt-auth.auto_refresh.grace_period', 3600)
            || $this->storage->hasAutoRefreshBeenProcessed($jwt))) {
            return null;
        }

        // Get user to refresh with updated props
        $user = UserUtil::getUserByIdentifier($jwt->payload->sub);
        if (! $user) {
            return null;
        }

        // Calculate original lifetime
        $originalLifetime = $jwt->payload->exp - $jwt->payload->iat;

        // Generate new JWT with updated props
        $refreshedTokenData = $user->generateJwt(lifetime: $originalLifetime);
        $refreshJwt = Jwt::decode($refreshedTokenData->accessToken);

        // Calculate TTL based on whether it's preemptive or grace period refresh
        if ($isPreemptive) {
            // For preemptive refresh, cache until the new token's expiration
            $ttl = $refreshJwt->payload->exp - time();
        } else {
            // For grace period refresh, use the configured cache TTL
            $ttl = config('jwt-auth.auto_refresh.cache_ttl', 10);
        }

        // Cache the new token
        $this->storage->storeAutoRefreshedToken(
            originalJwt: $jwt,
            refreshedJwt: $refreshJwt,
            ttl: $ttl
        );
        $this->storage->markAutoRefreshCompleted($jwt);

        request()->attributes->set('jwt_refreshed_token', $refreshJwt->encode());

        return $refreshJwt;
    }
}
