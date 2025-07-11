<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Interface for typed values that contain sensitive data requiring special logging treatment.
 * 
 * Any TypedValue working with confidential data should implement this interface
 * to ensure proper security handling during logging operations.
 */
interface SensitiveData
{
    /**
     * Returns the default logging policy for this data type.
     * 
     * This static method directly connects the data type with security rules,
     * providing a clear contract for how this type should be handled in logs.
     */
    public static function getLoggingPolicy(): LoggingPolicy;

    /**
     * Returns a safe representation of the value for logging purposes.
     * 
     * This method applies the obfuscation logic according to the logging policy,
     * returning a primitive value (string, number, bool) that is safe to write to logs.
     * 
     * @return string|int|float|bool Safe representation for logging
     */
    public function getSafeLoggableRepresentation();
} 