<?php

namespace Korioinc\JwtAuth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null refreshToken(bool $force = true)
 */
class JwtAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'jwt-auth';
    }
}
