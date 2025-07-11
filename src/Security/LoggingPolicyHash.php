<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Trait for implementing SHA256 hashing logging policy.
 * 
 * This trait provides a default implementation for values that should be
 * hashed when logged (e.g., session IDs, user IDs, API keys).
 */
trait LoggingPolicyHash
{
    public static function getLoggingPolicy(): LoggingPolicy
    {
        return LoggingPolicy::hashSha256();
    }

    public function getSafeLoggableRepresentation(): string
    {
        return hash('sha256', (string) $this->toNative());
    }

    /**
     * Generate hash with custom algorithm.
     * 
     * @param string $algorithm Hash algorithm (sha256, sha1, md5, etc.)
     */
    protected function hashWith(string $algorithm): string
    {
        return hash($algorithm, (string) $this->toNative());
    }

    /**
     * Generate hash with salt for additional security.
     * 
     * @param string $salt Salt to add before hashing
     */
    protected function hashWithSalt(string $salt): string
    {
        return hash('sha256', $salt . (string) $this->toNative());
    }
} 