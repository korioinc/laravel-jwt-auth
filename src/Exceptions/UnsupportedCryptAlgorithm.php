<?php

namespace Korioinc\JwtAuth\Exceptions;

class UnsupportedCryptAlgorithm extends JwtException
{
    public function __construct(string $algorithm, array $context = [])
    {
        parent::__construct("Unsupported JWT algorithm: {$algorithm}", 400, array_merge(['algorithm' => $algorithm], $context));
    }
}
