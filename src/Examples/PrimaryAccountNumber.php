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
use JsonSerializable;

/**
 * Primary Account Number (Credit Card Number) with comprehensive security.
 * 
 * Example implementation showing how to handle credit card numbers securely.
 * Uses different strategies for different contexts.
 */
final class PrimaryAccountNumber implements Typed, SensitiveData, JsonSerializable, PersistentData, SecurityPolicyProvider
{
    use TypedValue;
    use GenericSecurityTrait;

    /**
     * Define security policy for credit card numbers.
     */
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::financial(); // Use the preset financial policy
    }

    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        
        if (!is_string($value) && !is_numeric($value)) {
            $result->addError('Primary Account Number must be a string or numeric');
            return $result;
        }
        
        $number = preg_replace('/\s+/', '', (string) $value);
        
        if (!preg_match('/^\d{13,19}$/', $number)) {
            $result->addError('Primary Account Number must be 13-19 digits');
            return $result;
        }
        
        // Luhn algorithm validation
        if (!self::isValidLuhn($number)) {
            $result->addError('Primary Account Number failed Luhn validation');
        }
        
        return $result;
    }

    /**
     * Override fromNative to normalize spaces
     */
    public static function fromNative($native): Typed
    {
        // Strip spaces before validation
        if (is_string($native) || is_numeric($native)) {
            $native = preg_replace('/\s+/', '', (string) $native);
        }
        
        static::assert($native);
        $typedValue = new static();
        $typedValue->value = $native;
        return $typedValue;
    }

    /**
     * Override tryParse to normalize spaces before storing
     */
    public static function tryParse($value, ?Typed &$typed = null, ?ValidationResult &$result = null): bool
    {
        $result = static::validate($value);
        if($result->fails()) {
            return false;
        }
        
        // Normalize the value before storing
        $normalizedValue = $value;
        if (is_string($value) || is_numeric($value)) {
            $normalizedValue = preg_replace('/\s+/', '', (string) $value);
        }
        
        $typed = new static();
        $typed->value = $normalizedValue;
        return true;
    }

    /**
     * Custom masking for PAN showing first 6 and last 4 digits (industry standard).
     */
    public function getSafeLoggableRepresentation(): string
    {
        return $this->maskCustom(6, 4, '*');
    }

    /**
     * Get the card brand based on the PAN.
     */
    public function getCardBrand(): string
    {
        $number = (string) $this->value;
        
        if (preg_match('/^4/', $number)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5]/', $number)) {
            return 'MasterCard';
        } elseif (preg_match('/^3[47]/', $number)) {
            return 'American Express';
        } elseif (preg_match('/^6(?:011|5)/', $number)) {
            return 'Discover';
        }
        
        return 'Unknown';
    }

    /**
     * Luhn algorithm validation for credit card numbers.
     */
    private static function isValidLuhn(string $number): bool
    {
        $sum = 0;
        $alternate = false;
        
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];
            
            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = ($digit % 10) + 1;
                }
            }
            
            $sum += $digit;
            $alternate = !$alternate;
        }
        
        return ($sum % 10) === 0;
    }

    /**
     * Returns a safe representation for JSON serialization.
     * Only shows the last 4 digits and card brand.
     */
    public function jsonSerialize(): array
    {
        $valueStr = (string) $this->value;
        return [
            'last_four' => substr($valueStr, -4),
            'brand' => $this->getCardBrand(),
            'type' => 'card'
        ];
    }

    /**
     * Returns an encrypted representation for database storage.
     * In production, this would use a proper encryption service.
     */
    public function getPersistentRepresentation(): string
    {
        // In production, use proper encryption service
        // This is a simple example showing the concept
        return 'ENC_PAN_' . base64_encode($this->value);
    }
} 