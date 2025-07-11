<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Examples;

use dzentota\TypedValue\Security\LoggingPolicyHash;
use dzentota\TypedValue\Security\SensitiveData;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;

/**
 * Session ID with SHA256 hashing logging policy.
 * 
 * Example implementation showing how to handle session IDs securely.
 * Uses hashing to enable correlation in logs while protecting the actual session ID.
 */
final class SessionId implements Typed, SensitiveData
{
    use TypedValue;
    use LoggingPolicyHash;

    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        
        if (!is_string($value)) {
            $result->addError('Session ID must be a string');
            return $result;
        }
        
        if (strlen($value) < 16) {
            $result->addError('Session ID must be at least 16 characters long');
        }
        
        if (strlen($value) > 128) {
            $result->addError('Session ID must not exceed 128 characters');
        }
        
        // Check for valid characters (alphanumeric and some special chars)
        if (!preg_match('/^[A-Za-z0-9\-_+\/=]+$/', $value)) {
            $result->addError('Session ID contains invalid characters');
        }
        
        return $result;
    }

    /**
     * Generate a cryptographically secure session ID.
     */
    public static function generate(int $length = 32): self
    {
        $bytes = random_bytes($length);
        $sessionId = bin2hex($bytes);
        
        return self::fromNative($sessionId);
    }

    /**
     * Check if the session ID appears to be expired based on format patterns.
     * This is a simple heuristic - real expiration should be handled by session storage.
     */
    public function appearsExpired(): bool
    {
        // Simple heuristic: very short IDs might indicate expired/invalid sessions
        return strlen($this->toNative()) < 20;
    }

    /**
     * Get a shortened version for debugging (first 8 chars + hash).
     * This is safer than the full ID but still allows some identification.
     */
    public function getDebugRepresentation(): string
    {
        $sessionId = $this->toNative();
        $prefix = substr($sessionId, 0, 8);
        $hash = substr($this->getSafeLoggableRepresentation(), 0, 8);
        
        return $prefix . '...' . $hash;
    }

    /**
     * Custom hashing with salt for additional security.
     */
    public function getSafeLoggableRepresentation(): string
    {
        // Use a fixed salt for consistent hashing across requests
        return $this->hashWithSalt('session_salt_2024');
    }
} 