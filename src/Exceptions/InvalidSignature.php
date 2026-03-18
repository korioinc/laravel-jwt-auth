<?php

namespace Korioinc\JwtAuth\Exceptions;

class InvalidSignature extends JwtException
{
    public function __construct(array $context = [])
    {
        parent::__construct('JWT signature verification failed', 401, $context);
    }
}
