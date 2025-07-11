<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Examples;

use dzentota\TypedValue\Security\LoggingPolicyTokenize;
use dzentota\TypedValue\Security\SensitiveData;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;

/**
 * Email Address with tokenization logging policy.
 * 
 * Example implementation showing how to handle email addresses securely.
 * Uses tokenization to maintain correlation while protecting privacy.
 */
final class EmailAddress implements Typed, SensitiveData
{
    use TypedValue;
    use LoggingPolicyTokenize;

    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        
        if (!is_string($value)) {
            $result->addError('Email address must be a string');
            return $result;
        }
        
        // Check length first before format validation
        if (strlen($value) > 254) {
            $result->addError('Email address is too long (maximum 254 characters)');
            return $result;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $result->addError('Invalid email address format');
        }
        
        return $result;
    }

    /**
     * Custom tokenization with email-specific prefix.
     */
    public function getSafeLoggableRepresentation(): string
    {
        return $this->tokenizeWithPrefix('EMAIL');
    }

    /**
     * Get the domain part of the email (safe for logging).
     */
    public function getDomain(): string
    {
        return substr(strrchr($this->toNative(), '@'), 1);
    }

    /**
     * Get the local part (username) of the email.
     * Note: This returns the actual local part - use with caution.
     */
    public function getLocalPart(): string
    {
        return strstr($this->toNative(), '@', true);
    }

    /**
     * Check if email is from a specific domain.
     */
    public function isFromDomain(string $domain): bool
    {
        return strcasecmp($this->getDomain(), $domain) === 0;
    }

    /**
     * Check if email appears to be a corporate email.
     */
    public function isCorporateEmail(): bool
    {
        $personalDomains = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'aol.com', 'icloud.com', 'mail.com', 'protonmail.com'
        ];
        
        return !in_array(strtolower($this->getDomain()), $personalDomains);
    }
} 