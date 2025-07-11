<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Interface for typed values that contain sensitive data requiring special treatment.
 * 
 * Any TypedValue working with confidential data should implement this interface
 * to ensure proper security handling during various operations.
 * 
 * This interface uses the SecurityStrategy system for modern security handling.
 */
interface SensitiveData
{
    /**
     * Returns the security strategy for logging this data type.
     * 
     * Classes using the SecurityPolicyProvider system will have this
     * automatically implemented via the GenericSecurityTrait.
     * 
     * @return SecurityStrategy
     */
    public static function getLoggingSecurityStrategy(): SecurityStrategy;

    /**
     * Returns a safe representation of the value for logging purposes.
     * 
     * This method applies the obfuscation logic according to the security policy,
     * returning a primitive value (string, number, bool) that is safe to write to logs.
     * 
     * @return string|int|float|bool Safe representation for logging
     */
    public function getSafeLoggableRepresentation();
} 