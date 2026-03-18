<?php

namespace Korioinc\JwtAuth\Utils;

use Korioinc\JwtAuth\Exceptions\InvalidTokenStructure;

class StringUtil
{
    public static function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @throws InvalidTokenStructure
     */
    public static function decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidTokenStructure('Invalid base64url encoding');
        }

        return $decoded;
    }
}
