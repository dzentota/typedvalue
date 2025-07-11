<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Trait for implementing plaintext logging policy.
 * 
 * This trait provides a default implementation for values that can be
 * safely logged as-is (e.g., public IDs, status codes, public names).
 */
trait LoggingPolicyPlaintext
{
    public static function getLoggingPolicy(): LoggingPolicy
    {
        return LoggingPolicy::plaintext();
    }

    public function getSafeLoggableRepresentation()
    {
        return $this->toNative();
    }
} 