<?php

namespace Korioinc\JwtAuth\Exceptions;

class InvalidRefreshToken extends JwtException
{
    public function __construct(array $context = [])
    {
        parent::__construct('Invalid refresh token', 401, $context);
    }
}
