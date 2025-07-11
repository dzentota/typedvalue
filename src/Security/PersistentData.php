<?php

namespace dzentota\TypedValue\Security;

/**
 * Indicates that an object has a special representation for storage
 * in persistent storage (e.g., database).
 * 
 * This interface ensures that sensitive data is never stored in plain text
 * by requiring the object to define its own safe storage representation.
 */
interface PersistentData
{
    /**
     * Returns the value in a format safe for storage.
     * This can be an encrypted string, token, hash, or other secure representation.
     *
     * @return string|int|float|bool|null
     */
    public function getPersistentRepresentation(): mixed;
} 