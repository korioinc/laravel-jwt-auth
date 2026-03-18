<?php

namespace Korioinc\JwtAuth\Interfaces;

use Korioinc\JwtAuth\Data\ResponseTokenData;

interface JwtUserInterface
{
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getAuthIdentifier();

    /**
     * Generate a JWT token for the user.
     *
     * @param  int|null  $lifetime  Token lifetime in seconds
     */
    public function generateJwt(?int $lifetime = null): ResponseTokenData;

    /**
     * Get additional properties to be included in the JWT payload.
     */
    public function getJwtProps(): array;
}
