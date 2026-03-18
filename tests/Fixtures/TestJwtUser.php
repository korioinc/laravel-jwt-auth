<?php

namespace Korioinc\JwtAuth\Tests\Fixtures;

use Korioinc\JwtAuth\Interfaces\JwtUserInterface;
use Korioinc\JwtAuth\Traits\JwtTrait;

final class TestJwtUser implements JwtUserInterface
{
    use JwtTrait;

    public function __construct(
        private readonly int $id,
        private readonly array $props = []
    ) {}

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getJwtProps(): array
    {
        return $this->props;
    }
}
