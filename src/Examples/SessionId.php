<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Examples;

use dzentota\TypedValue\Security\GenericSecurityTrait;
use dzentota\TypedValue\Security\PersistentData;
use dzentota\TypedValue\Security\SecurityPolicy;
use dzentota\TypedValue\Security\SecurityPolicyProvider;
use dzentota\TypedValue\Security\SecurityStrategy;
use dzentota\TypedValue\Security\SensitiveData;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;

/**
 * Session ID with comprehensive security policies.
 * 
 * Example implementation showing how to handle session IDs securely.
 * Uses different strategies for different contexts.
 */
final class SessionId implements Typed, SensitiveData, PersistentData, SecurityPolicyProvider
{
    use TypedValue;
    use GenericSecurityTrait;

    /**
     * Define security policy for session IDs.
     */
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create()
            ->logging(SecurityStrategy::HASH_SHA256)   // Hash for logs
            ->persistence(SecurityStrategy::HASH_SHA256) // Hash for DB storage
            ->reporting(SecurityStrategy::PROHIBIT)    // Never in reports
            ->serialization(SecurityStrategy::TOKENIZE) // Token for APIs
            ->build();
    }

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
        return $this->hashWithSalt('session_salt_2024');
    }

    /**
     * Returns a hashed representation for database storage.
     * This ensures session IDs can be stored securely while maintaining lookup capability.
     */
    public function getPersistentRepresentation(): string
    {
        return $this->getSafeLoggableRepresentation();
    }

    /**
     * Get estimated session duration in minutes (for analytics).
     * This is a mock implementation for demonstration.
     */
    public function getSessionDuration(): int
    {
        // In a real implementation, this would calculate based on creation time
        // For now, return a mock value based on ID length (longer IDs = longer sessions)
        return min(120, max(5, strlen($this->toNative()) * 2));
    }
} 