<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Examples;

use dzentota\TypedValue\Security\LoggingPolicyMask;
use dzentota\TypedValue\Security\SensitiveData;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;

/**
 * Primary Account Number (Credit Card Number) with partial masking logging policy.
 * 
 * Example implementation showing how to handle credit card numbers securely.
 * Uses partial masking to show only the last 4 digits in logs.
 */
final class PrimaryAccountNumber implements Typed, SensitiveData
{
    use TypedValue;
    use LoggingPolicyMask;

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
} 