<?php

namespace Korioinc\JwtAuth\Exceptions;

class FutureToken extends JwtException
{
    public function __construct(array $context = [])
    {
        parent::__construct('JWT token is not yet valid', 401, $context);
    }
}
