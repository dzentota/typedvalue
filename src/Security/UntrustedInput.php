<?php

namespace dzentota\TypedValue\Security;

/**
 * Marker interface for data from untrusted sources.
 * 
 * Objects implementing this interface should never be used directly in business logic
 * or passed to "sinks". They must be parsed and validated into domain primitives first.
 * 
 * This interface enforces the "Parse, don't validate" principle at the architecture level.
 */
interface UntrustedInput
{
    /**
     * Returns the raw, unvalidated value.
     * 
     * @return mixed
     */
    public function getRawValue(): mixed;
} 