<?php

namespace Korioinc\JwtAuth\Data;

final readonly class RefreshTokenData
{
    public function __construct(
        public string $identifier,
        public int $lifetime,
    ) {}

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'lifetime' => $this->lifetime,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            identifier: $data['identifier'],
            lifetime: $data['lifetime'],
        );
    }
}
