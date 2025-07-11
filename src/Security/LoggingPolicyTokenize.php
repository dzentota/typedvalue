<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Trait for implementing tokenization logging policy.
 * 
 * This trait provides a default implementation for values that should be
 * tokenized when logged (e.g., email addresses, user names).
 * 
 * Note: This is a basic implementation. In production, you should integrate
 * with a proper tokenization service.
 */
trait LoggingPolicyTokenize
{
    public static function getLoggingPolicy(): LoggingPolicy
    {
        return LoggingPolicy::tokenize();
    }

    public function getSafeLoggableRepresentation(): string
    {
        // Basic tokenization using hash - in production, use a proper tokenization service
        return 'TOKEN_' . substr(hash('sha256', (string) $this->toNative()), 0, 16);
    }

    /**
     * Generate token with custom prefix.
     * 
     * @param string $prefix Prefix for the generated token
     */
    protected function tokenizeWithPrefix(string $prefix): string
    {
        return $prefix . '_' . substr(hash('sha256', (string) $this->toNative()), 0, 16);
    }

    /**
     * Generate deterministic token (same input always produces same token).
     * Useful for correlation in logs while maintaining anonymity.
     * 
     * @param string $key Secret key for deterministic generation
     */
    protected function deterministicToken(string $key = ''): string
    {
        $input = $key . (string) $this->toNative();
        return 'DET_' . substr(hash('sha256', $input), 0, 16);
    }
} 