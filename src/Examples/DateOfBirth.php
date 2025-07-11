<?php

namespace dzentota\TypedValue\Examples;

use DateTimeImmutable;
use dzentota\TypedValue\Security\GenericSecurityTrait;
use dzentota\TypedValue\Security\PersistentData;
use dzentota\TypedValue\Security\ReportableData;
use dzentota\TypedValue\Security\SecurityPolicy;
use dzentota\TypedValue\Security\SecurityPolicyProvider;
use dzentota\TypedValue\Security\SecurityStrategy;
use dzentota\TypedValue\Security\SensitiveData;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;

/**
 * Example of a date of birth value object that implements ReportableData.
 * 
 * This shows how to provide anonymized data for analytics while protecting
 * the actual birth date from exposure in reports and database storage.
 */
final class DateOfBirth implements Typed, SensitiveData, ReportableData, PersistentData, SecurityPolicyProvider
{
    use TypedValue;
    use GenericSecurityTrait;

    /**
     * Validate a date of birth value.
     * 
     * @param mixed $value
     * @return ValidationResult
     */
    public static function validate($value): ValidationResult
    {
        return static::tryParseValue($value);
    }

    /**
     * Parse a date of birth from various formats.
     * 
     * @param mixed $value
     * @return ValidationResult
     */
    protected static function tryParseValue($value): ValidationResult
    {
        $result = new ValidationResult();
        $date = null;
        
        if ($value instanceof DateTimeImmutable) {
            $date = $value;
        } elseif (is_string($value)) {
            // Try to parse common date formats
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y-m-d H:i:s'];
            
            foreach ($formats as $format) {
                $parsedDate = DateTimeImmutable::createFromFormat($format, $value);
                if ($parsedDate !== false) {
                    // Check if the parsing was strict (no warnings/errors)
                    $errors = DateTimeImmutable::getLastErrors();
                    if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                        continue; // Skip this format if there were warnings/errors
                    }
                    
                    // Reset time to avoid issues with time components
                    $date = $parsedDate->setTime(0, 0, 0);
                    break;
                }
            }
        }
        
        if ($date === null) {
            $result->addError('Invalid date of birth');
            return $result;
        }
        
        // Validate that it's a reasonable birth date
        $now = new DateTimeImmutable();
        $minDate = (new DateTimeImmutable())->modify('-150 years')->setTime(0, 0, 0);
        $maxDate = (new DateTimeImmutable())->modify('-1 day')->setTime(0, 0, 0);
        
        if ($date < $minDate || $date > $maxDate) {
            $result->addError('Invalid date of birth');
            return $result;
        }
        
        return $result; // Success - no errors
    }

    /**
     * Override tryParse to convert string values to DateTimeImmutable
     */
    public static function tryParse($value, ?Typed &$typed = null, ?ValidationResult &$result = null): bool
    {
        $result = static::validate($value);
        if($result->fails()) {
            return false;
        }
        
        // Convert value to DateTimeImmutable if needed
        $parsedValue = $value;
        if ($value instanceof DateTimeImmutable) {
            $parsedValue = $value;
        } elseif (is_string($value)) {
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y-m-d H:i:s'];
            
            foreach ($formats as $format) {
                $date = DateTimeImmutable::createFromFormat($format, $value);
                if ($date !== false) {
                    // Check if the parsing was strict (no warnings/errors)
                    $errors = DateTimeImmutable::getLastErrors();
                    if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                        continue; // Skip this format if there were warnings/errors
                    }
                    
                    $parsedValue = $date->setTime(0, 0, 0);
                    break;
                }
            }
        }
        
        $typed = new static();
        $typed->value = $parsedValue;
        return true;
    }

    /**
     * Override fromNative to convert string values to DateTimeImmutable
     */
    public static function fromNative($native): Typed
    {
        $parsedValue = $native;
        
        // Convert value to DateTimeImmutable if needed
        if ($native instanceof DateTimeImmutable) {
            $parsedValue = $native;
        } elseif (is_string($native)) {
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y-m-d H:i:s'];
            
            foreach ($formats as $format) {
                $date = DateTimeImmutable::createFromFormat($format, $native);
                if ($date !== false) {
                    // Check if the parsing was strict (no warnings/errors)
                    $errors = DateTimeImmutable::getLastErrors();
                    if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                        continue; // Skip this format if there were warnings/errors
                    }
                    
                    $parsedValue = $date->setTime(0, 0, 0);
                    break;
                }
            }
        }
        
        static::assert($parsedValue);
        $typedValue = new static();
        $typedValue->value = $parsedValue;
        return $typedValue;
    }

    /**
     * Returns the birth date as a string.
     * 
     * @return string
     */
    public function toNative(): string
    {
        return $this->value->format('Y-m-d');
    }

    /**
     * Override getAnonymizedReportValue to provide age instead of raw date.
     */
    public function getAnonymizedReportValue(): int
    {
        $now = new DateTimeImmutable();
        return $this->value->diff($now)->y;
    }

    /**
     * Returns the current age.
     * 
     * @return int
     */
    public function getAge(): int
    {
        return $this->getAnonymizedReportValue();
    }

    /**
     * Returns the age group for broader analytics.
     * 
     * @return string
     */
    public function getAgeGroup(): string
    {
        $age = $this->getAge();
        
        if ($age < 18) return 'Under 18';
        if ($age < 25) return '18-24';
        if ($age < 35) return '25-34';
        if ($age < 45) return '35-44';
        if ($age < 55) return '45-54';
        if ($age < 65) return '55-64';
        
        return '65+';
    }

    /**
     * Define security policy for date of birth data.
     */
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create()
            ->logging(SecurityStrategy::HASH_SHA256)     // Hash for logs
            ->persistence(SecurityStrategy::HASH_SHA256) // Hash for DB storage
            ->reporting(SecurityStrategy::PLAINTEXT)     // Age is safe for reports
            ->serialization(SecurityStrategy::HASH_SHA256) // Hash for APIs
            ->build();
    }

    /**
     * Returns a hashed representation for database storage.
     * This ensures birth dates are never stored in plain text.
     */
    public function getPersistentRepresentation(): string
    {
        return $this->getSafeLoggableRepresentation();
    }
} 