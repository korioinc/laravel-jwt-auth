<?php

namespace Korioinc\JwtAuth\Data;

use Korioinc\JwtAuth\Exceptions\InvalidTokenStructure;
use Korioinc\JwtAuth\Utils\StringUtil;

class JwtPayload extends AbstractJwtComponent
{
    public function __construct(
        public ?string $jti,
        public int $iat,
        public int $exp,
        public ?string $sub = null,
        public ?string $iss = null,
        public ?string $aud = null,
        public ?array $amr = null,
        public ?int $nbf = null,
        public ?array $props = null,
    ) {}

    /**
     * @throws InvalidTokenStructure
     */
    public static function decode(string $string): self
    {
        $data = self::decodeString($string);

        $instance = new self(
            jti: $data['jti'] ?? null,
            iat: $data['iat'] ?? time(),
            exp: $data['exp'] ?? time(),
            sub: $data['sub'] ?? null,
            iss: $data['iss'] ?? null,
            aud: $data['aud'] ?? null,
            amr: $data['amr'] ?? null,
            nbf: $data['nbf'] ?? null,
            props: $data['props'] ?? self::getProps($data)
        );
        $instance->setSource($string);

        return $instance;
    }

    private static function getProps(array $props): ?array
    {
        $data = [];
        $keys = ['jti', 'iat', 'exp', 'sub', 'iss', 'aud', 'amr', 'nbf'];
        foreach ($props as $key => $prop) {
            if (in_array($key, $keys)) {
                continue;
            }
            $data[$key] = $prop;
        }
        if (count($data) === 0) {
            return null;
        }

        return $data;
    }

    public function encode(): string
    {
        $data = [];
        if ($this->jti !== null) {
            $data['jti'] = $this->jti;
        }
        $data['iat'] = $this->iat;
        $data['exp'] = $this->exp;
        if ($this->sub !== null) {
            $data['sub'] = $this->sub;
        }
        if ($this->iss !== null) {
            $data['iss'] = $this->iss;
        }
        if ($this->aud !== null) {
            $data['aud'] = $this->aud;
        }
        if ($this->amr !== null) {
            $data['amr'] = $this->amr;
        }
        if ($this->nbf !== null) {
            $data['nbf'] = $this->nbf;
        }
        if ($this->props !== null) {
            $data['props'] = $this->props;
        }

        return StringUtil::encode(json_encode($data));
    }
}
