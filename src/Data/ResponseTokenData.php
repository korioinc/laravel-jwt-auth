<?php

namespace Korioinc\JwtAuth\Data;

final readonly class ResponseTokenData
{
    public function __construct(
        public string $accessToken,
        public ?int $expiresAt,
        public ?string $refreshToken
    ) {}
}
