<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

use LogicException;

/**
 * Trait for implementing read-once behavior for highly sensitive data.
 * 
 * This trait ensures that sensitive values like passwords or CVV codes
 * can only be accessed once, significantly reducing the window of potential
 * compromise. After the first access, the value becomes unavailable.
 */
trait ReadOnce
{
    private bool $hasBeenRead = false;

    /**
     * Gets the value exactly once. Subsequent calls will throw an exception.
     * 
     * @return mixed The stored value
     * @throws LogicException If the value has already been read
     */
    public function getValue()
    {
        if ($this->hasBeenRead) {
            throw new LogicException(
                sprintf('Value of type %s has already been consumed and cannot be read again.', static::class)
            );
        }

        $this->hasBeenRead = true;
        return $this->value;
    }

    /**
     * Checks if the value has been read without consuming it.
     */
    public function hasBeenConsumed(): bool
    {
        return $this->hasBeenRead;
    }

    /**
     * Override the base toNative to use the read-once behavior.
     */
    public function toNative()
    {
        return $this->getValue();
    }
} 