<?php

namespace Korioinc\JwtAuth\Traits;

use Illuminate\Support\Str;
use Korioinc\JwtAuth\Data\Jwt;
use Korioinc\JwtAuth\Data\JwtHeader;
use Korioinc\JwtAuth\Data\JwtPayload;
use Korioinc\JwtAuth\Data\ResponseTokenData;
use Korioinc\JwtAuth\Enums\AlgorithmEnum;
use Korioinc\JwtAuth\Interfaces\JwtUserInterface;
use Korioinc\JwtAuth\Services\AuthService;

trait JwtTrait
{
    public function generateJwt(?int $lifetime = null): ResponseTokenData
    {
        $user = $this->getJwtUser();
        $lifetimeSeconds = $lifetime ?? (int) config('jwt-auth.access_token.lifetime', 3600);
        $expiresAt = now()->addSeconds($lifetimeSeconds);
        $algorithm = AlgorithmEnum::from(config('jwt-auth.algorithm', 'HS256'));

        $customProps = $user->getJwtProps();

        $token = new Jwt(
            new JwtHeader($algorithm),
            new JwtPayload(
                jti: $user->getAuthIdentifier().':'.Str::random(32),
                iat: now()->timestamp,
                exp: $expiresAt->timestamp,
                sub: $user->getAuthIdentifier(),
                iss: null,
                aud: null,
                props: ! empty($customProps) ? $customProps : null
            )
        );

        return app(AuthService::class)->generateAccessToken($user, $token, $lifetimeSeconds);
    }

    private function getJwtUser(): JwtUserInterface
    {
        return $this;
    }
}
