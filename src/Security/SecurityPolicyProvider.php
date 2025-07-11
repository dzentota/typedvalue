<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Interface for objects that provide security policies.
 * 
 * This interface allows typed values to define how they should be handled
 * in different security contexts (logging, persistence, reporting, etc.).
 */
interface SecurityPolicyProvider
{
    /**
     * Returns the security policy for this object.
     */
    public static function getSecurityPolicy(): SecurityPolicy;
} 