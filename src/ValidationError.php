<?php
declare(strict_types=1);

namespace dzentota\TypedValue;

/**
 * Validation error message.
 *
 * Represents a single error message.
 * Based on https://github.com/selective-php/validation
 */
final class ValidationError
{
    /**
     * @var string
     */
    private string $message;

    /**
     * @var string|null
     */
    private ?string $field;


    /**
     * Constructor.
     *
     * @param string $message The Message
     * @param string|null $field
     */
    public function __construct(string $message, ?string $field = null)
    {
        $this->message = $message;
        $this->field = $field;
    }

    /**
     * Returns the message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns the field name.
     *
     * @return string|null The field name
     */
    public function getField(): ?string
    {
        return $this->field;
    }
}
