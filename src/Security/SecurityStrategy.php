<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Security strategy enum for sensitive data handling.
 * 
 * Defines the different strategies for protecting sensitive data.
 */
enum SecurityStrategy: string
{
    case PROHIBIT = 'prohibit';
    case MASK_PARTIAL = 'mask_partial';
    case HASH_SHA256 = 'hash_sha256';
    case TOKENIZE = 'tokenize';
    case ENCRYPT = 'encrypt';
    case PLAINTEXT = 'plaintext';

    public function isProhibit(): bool
    {
        return $this === self::PROHIBIT;
    }

    public function isMaskPartial(): bool
    {
        return $this === self::MASK_PARTIAL;
    }

    public function isHashSha256(): bool
    {
        return $this === self::HASH_SHA256;
    }

    public function isTokenize(): bool
    {
        return $this === self::TOKENIZE;
    }

    public function isEncrypt(): bool
    {
        return $this === self::ENCRYPT;
    }

    public function isPlaintext(): bool
    {
        return $this === self::PLAINTEXT;
    }

    public function getLabel(): string
    {
        return match($this) {
            self::PROHIBIT => 'Prohibited',
            self::MASK_PARTIAL => 'Partial Masking',
            self::HASH_SHA256 => 'SHA256 Hashing',
            self::TOKENIZE => 'Tokenization',
            self::ENCRYPT => 'Encryption',
            self::PLAINTEXT => 'Plain Text',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::PROHIBIT => 'Never expose the data - throw exception on access',
            self::MASK_PARTIAL => 'Show only partial data (e.g., last 4 digits)',
            self::HASH_SHA256 => 'One-way hash for correlation without exposure',
            self::TOKENIZE => 'Replace with a correlation token',
            self::ENCRYPT => 'Reversible encryption for secure storage',
            self::PLAINTEXT => 'Safe to expose as-is (non-sensitive data)',
        };
    }

    public function getSecurityLevel(): int
    {
        return match($this) {
            self::PROHIBIT => 10,      // Highest security
            self::ENCRYPT => 9,
            self::HASH_SHA256 => 8,
            self::TOKENIZE => 7,
            self::MASK_PARTIAL => 5,
            self::PLAINTEXT => 1,      // Lowest security
        };
    }

    public function isReversible(): bool
    {
        return match($this) {
            self::ENCRYPT => true,
            self::PLAINTEXT => true,
            default => false,
        };
    }

    public function isObfuscated(): bool
    {
        return match($this) {
            self::PLAINTEXT => false,
            default => true,
        };
    }
} 