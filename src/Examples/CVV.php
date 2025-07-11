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
 * Card Verification Value (CVV) with prohibition from logs and read-once behavior.
 * 
 * Example implementation showing how to handle CVV codes securely.
 * Combines prohibition from logs with read-once access pattern for maximum security.
 */
final class CVV implements Typed, ProhibitedFromLogs, SecurityPolicyProvider
{
    use TypedValue, GenericSecurityTrait, ReadOnce {
        ReadOnce::toNative insteadof TypedValue;
    }

    /**
     * Define security policy for CVV codes.
     */
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::prohibited(); // Use the preset prohibited policy
    }

    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        
        if (!is_string($value) && !is_numeric($value)) {
            $result->addError('CVV must be a string or numeric value');
            return $result;
        }
        
        $cvv = (string) $value;
        
        // CVV must be 3 or 4 digits
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $result->addError('CVV must be 3 or 4 digits');
        }
        
        return $result;
    }

    /**
     * Verify CVV against expected value.
     * This consumes the CVV value (read-once behavior).
     */
    public function matches(string $expectedCVV): bool
    {
        return $this->getValue() === $expectedCVV;
    }

    /**
     * Get CVV length (3 for most cards, 4 for American Express).
     * This doesn't consume the value.
     */
    public function getLength(): int
    {
        if ($this->hasBeenConsumed()) {
            return 0; // Cannot determine length of consumed CVV
        }
        
        return strlen($this->value);
    }

    /**
     * Check if this appears to be an American Express CVV (4 digits).
     * This doesn't consume the value.
     */
    public function isAmexCVV(): bool
    {
        return $this->getLength() === 4;
    }

    /**
     * Perform CVV verification and immediately clear the value.
     * This is the recommended way to use CVV for verification.
     */
    public function verifyAndClear(string $expectedCVV): bool
    {
        try {
            return $this->matches($expectedCVV);
        } catch (\Exception $e) {
            // CVV already consumed or other error
            return false;
        }
    }
} 