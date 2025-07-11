<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Logging policy for sensitive data.
 * 
 * This class provides enum-like behavior for PHP 7.4+ compatibility.
 * Can be easily migrated to native enum when minimum PHP version is bumped to 8.1+
 */
final class LoggingPolicy
{
    public const PROHIBIT = 'prohibit';
    public const MASK_PARTIAL = 'mask_partial';
    public const HASH_SHA256 = 'hash_sha256';
    public const TOKENIZE = 'tokenize';
    public const ENCRYPT = 'encrypt';
    public const PLAINTEXT = 'plaintext';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function prohibit(): self
    {
        return new self(self::PROHIBIT);
    }

    public static function maskPartial(): self
    {
        return new self(self::MASK_PARTIAL);
    }

    public static function hashSha256(): self
    {
        return new self(self::HASH_SHA256);
    }

    public static function tokenize(): self
    {
        return new self(self::TOKENIZE);
    }

    public static function encrypt(): self
    {
        return new self(self::ENCRYPT);
    }

    public static function plaintext(): self
    {
        return new self(self::PLAINTEXT);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isProhibit(): bool
    {
        return $this->value === self::PROHIBIT;
    }

    public function isMaskPartial(): bool
    {
        return $this->value === self::MASK_PARTIAL;
    }

    public function isHashSha256(): bool
    {
        return $this->value === self::HASH_SHA256;
    }

    public function isTokenize(): bool
    {
        return $this->value === self::TOKENIZE;
    }

    public function isEncrypt(): bool
    {
        return $this->value === self::ENCRYPT;
    }

    public function isPlaintext(): bool
    {
        return $this->value === self::PLAINTEXT;
    }

    public function equals(LoggingPolicy $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
} 