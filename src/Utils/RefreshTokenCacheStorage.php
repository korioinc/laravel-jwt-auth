<?php

namespace Korioinc\JwtAuth\Utils;

use Illuminate\Support\Facades\Cache;
use Korioinc\JwtAuth\Data\Jwt;
use Korioinc\JwtAuth\Data\RefreshTokenData;
use Korioinc\JwtAuth\Interfaces\JwtUserInterface;
use Korioinc\JwtAuth\Interfaces\RefreshTokenStorageInterface;

class RefreshTokenCacheStorage implements RefreshTokenStorageInterface
{
    public function getUser(string $refreshToken): ?JwtUserInterface
    {
        $data = $this->getData($refreshToken);
        if (! $data) {
            return null;
        }

        return UserUtil::getUserByIdentifier($data->identifier);
    }

    public function getData(string $refreshToken): ?RefreshTokenData
    {
        $data = Cache::get($this->getCacheKey($refreshToken));
        if (! $data) {
            return null;
        }

        return RefreshTokenData::fromArray($data);
    }

    private function getCacheKey(string $refreshToken): string
    {
        return 'jwt:rt:'.md5($refreshToken);
    }

    public function create(JwtUserInterface $user, string $refreshToken, int $expiresAt, int $lifetime): void
    {
        $ttl = $expiresAt - time();
        $data = new RefreshTokenData(
            identifier: $user->getAuthIdentifier(),
            lifetime: $lifetime,
        );

        Cache::put(
            key: $this->getCacheKey($refreshToken),
            value: $data->toArray(),
            ttl: $ttl
        );
    }

    public function delete(JwtUserInterface $user, string $refreshToken): void
    {
        Cache::forget($this->getCacheKey($refreshToken));
    }

    public function storeAutoRefreshedToken(Jwt $originalJwt, Jwt $refreshedJwt, int $ttl): void
    {
        $cacheKey = $this->getAutoRefreshCacheKey($originalJwt);

        Cache::put($cacheKey, $refreshedJwt->encode(), $ttl);
    }

    public function getAutoRefreshedToken(Jwt $jwt): ?string
    {
        $cacheKey = $this->getAutoRefreshCacheKey($jwt);

        return Cache::get($cacheKey);
    }

    public function markAutoRefreshCompleted(Jwt $jwt): void
    {
        $cacheKey = $this->getAutoRefreshProcessedKey($jwt);
        $gracePeriod = config('jwt-auth.auto_refresh.grace_period', 3600);

        // Calculate remaining time: (exp + grace_period) - current_time
        $ttl = ($jwt->payload->exp + $gracePeriod) - time();

        // Only cache if there's time remaining
        if ($ttl > 0) {
            Cache::put($cacheKey, true, $ttl);
        }
    }

    public function hasAutoRefreshBeenProcessed(Jwt $jwt): bool
    {
        $cacheKey = $this->getAutoRefreshProcessedKey($jwt);

        return Cache::has($cacheKey);
    }

    private function getAutoRefreshCacheKey(Jwt $jwt): string
    {
        return 'jwt:auto_refresh:token:'.$jwt->getCrc();
    }

    private function getAutoRefreshProcessedKey(Jwt $jwt): string
    {
        return 'jwt:auto_refresh:processed:'.$jwt->getCrc();
    }
}
