<?php

namespace dzentota\TypedValue\Security;

/**
 * Represents a string value from an untrusted source.
 * 
 * This class wraps raw input data and forces explicit validation
 * before the data can be used in business logic.
 */
final class UntrustedString implements UntrustedInput
{
    private $rawValue;

    /**
     * @param mixed $rawValue The raw, unvalidated input
     */
    public function __construct($rawValue)
    {
        $this->rawValue = $rawValue;
    }

    /**
     * Returns the raw, unvalidated value.
     * 
     * @return mixed
     */
    public function getRawValue(): mixed
    {
        return $this->rawValue;
    }
} 