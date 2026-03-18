<?php

namespace Korioinc\JwtAuth\Exceptions;

class InvalidTokenStructure extends JwtException
{
    public function __construct(string $message = 'Invalid JWT token structure', array $context = [])
    {
        parent::__construct($message, 400, $context);
    }
}
