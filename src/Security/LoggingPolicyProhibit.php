<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

use LogicException;

/**
 * Trait for implementing prohibited logging policy.
 * 
 * This trait provides a default implementation for values that should never
 * be logged (e.g., passwords, private keys, CVV codes).
 */
trait LoggingPolicyProhibit
{
    public static function getLoggingPolicy(): LoggingPolicy
    {
        return LoggingPolicy::prohibit();
    }

    public function getSafeLoggableRepresentation(): string
    {
        throw new LogicException(
            sprintf('Attempted to log a prohibited value of type: %s', static::class)
        );
    }
} 