<?php

namespace Korioinc\JwtAuth\Utils;

use Korioinc\JwtAuth\Enums\AlgorithmEnum;
use Korioinc\JwtAuth\Exceptions\InvalidSignature;

final readonly class Crypto
{
    public function __construct(private string $secretKey) {}

    public function encode(AlgorithmEnum $algorithm, string $payload): string
    {
        $signature = hash_hmac($algorithm->getAlgo(), $payload, $this->secretKey, true);

        return StringUtil::encode($signature);
    }

    /**
     * @throws InvalidSignature
     */
    public function verify(AlgorithmEnum $algorithm, string $payload, string $src): void
    {
        $expectedSignature = hash_hmac($algorithm->getAlgo(), $payload, $this->secretKey, true);
        $expectedSignatureBase64 = StringUtil::encode($expectedSignature);

        if (! hash_equals($expectedSignatureBase64, $src)) {
            throw new InvalidSignature;
        }
    }
}
