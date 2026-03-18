<?php

namespace Korioinc\JwtAuth\Exceptions;

class ExpiredToken extends JwtException
{
    public function __construct(array $context = [])
    {
        parent::__construct('JWT token has expired', 401, $context);
    }
}
