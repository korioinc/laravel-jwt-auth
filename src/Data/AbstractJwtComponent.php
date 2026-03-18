<?php

namespace Korioinc\JwtAuth\Data;

use Korioinc\JwtAuth\Exceptions\InvalidTokenStructure;
use Korioinc\JwtAuth\Utils\StringUtil;

abstract class AbstractJwtComponent
{
    protected string $source;

    /**
     * Encode the component to base64url string
     */
    abstract public function encode(): string;

    /**
     * Get the original source string (used for signature verification)
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Set the source string
     */
    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Helper method to build data Array from properties
     */
    protected function buildDataArray(array $mappings): array
    {
        $data = [];

        foreach ($mappings as $property => $key) {
            if ($this->$property !== null) {
                $data[$key] = $this->$property;
            }
        }

        return $data;
    }

    /**
     * Common decode logic
     *
     * @throws InvalidTokenStructure
     */
    protected static function decodeString(string $string): array
    {
        $decoded = StringUtil::decode($string);
        $data = json_decode($decoded, true);

        if ($data === null) {
            throw new InvalidTokenStructure('Invalid JSON in JWT component');
        }

        return $data;
    }
}
