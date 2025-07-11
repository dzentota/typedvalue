<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Examples;

use dzentota\TypedValue\Security\GenericSecurityTrait;
use dzentota\TypedValue\Security\ProhibitedFromLogs;
use dzentota\TypedValue\Security\ReadOnce;
use dzentota\TypedValue\Security\SecurityPolicy;
use dzentota\TypedValue\Security\SecurityPolicyProvider;
use dzentota\TypedValue\Security\SecurityStrategy;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;

/**
 * User Password with prohibition from logs and read-once behavior.
 * 
 * Example implementation showing how to handle passwords securely.
 * Combines prohibition from logs with read-once access pattern.
 */
final class UserPassword implements Typed, ProhibitedFromLogs, SecurityPolicyProvider
{
    use TypedValue, GenericSecurityTrait, ReadOnce {
        ReadOnce::toNative insteadof TypedValue;
    }

    /**
     * Define security policy for passwords.
     */
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::prohibited(); // Use the preset prohibited policy
    }

    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        
        if (!is_string($value)) {
            $result->addError('Password must be a string');
            return $result;
        }
        
        if (strlen($value) < 8) {
            $result->addError('Password must be at least 8 characters long');
        }
        
        if (!preg_match('/[A-Z]/', $value)) {
            $result->addError('Password must contain at least one uppercase letter');
        }
        
        if (!preg_match('/[a-z]/', $value)) {
            $result->addError('Password must contain at least one lowercase letter');
        }
        
        if (!preg_match('/[0-9]/', $value)) {
            $result->addError('Password must contain at least one number');
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            $result->addError('Password must contain at least one special character');
        }
        
        return $result;
    }

    /**
     * Verify password against a hash.
     * This consumes the password value (read-once behavior).
     */
    public function matches(string $hash): bool
    {
        return password_verify($this->getValue(), $hash);
    }

    /**
     * Hash the password for storage.
     * This consumes the password value (read-once behavior).
     */
    public function hash(): string
    {
        return password_hash($this->getValue(), PASSWORD_DEFAULT);
    }

    /**
     * Get password strength score (0-100).
     * This method doesn't consume the value, but checks if already consumed.
     */
    public function getStrength(): int
    {
        if ($this->hasBeenConsumed()) {
            return 0; // Cannot assess strength of consumed password
        }
        
        $score = 0;
        $password = $this->value; // Access directly without consuming
        
        // Length scoring
        $length = strlen($password);
        $score += min(25, $length * 2);
        
        // Character variety scoring
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 15;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 25;
        
        // Bonus for length
        if ($length >= 12) $score += 10;
        if ($length >= 16) $score += 5;
        
        return min(100, $score);
    }

    /**
     * Get password strength score (alias for getStrength).
     */
    public function getStrengthScore(): int
    {
        return $this->getStrength();
    }

    /**
     * Get hashed representation for database storage.
     * This consumes the password value (read-once behavior).
     */
    public function getHashedRepresentation(): string
    {
        return $this->hash();
    }
} 