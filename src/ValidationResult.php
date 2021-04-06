<?php
declare(strict_types=1);

namespace dzentota\TypedValue;

/**
 * Validation Result.
 *
 * Represents a container for the results of a validation request.
 *
 * A validation result collects together errors and messages.
 *
 * https://martinfowler.com/articles/replaceThrowWithNotification.html
 * Based on https://github.com/selective-php/validation
 */
final class ValidationResult
{
    /**
     * @var ValidationError[]
     */
    private array $errors = [];

    /**
     * Get all errors.
     *
     * @return ValidationError[] Errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error.
     *
     * @return ValidationError|null Error message
     */
    public function getFirstError(): ?ValidationError
    {
        return reset($this->errors) ?: null;
    }

    /**
     * Determine if the data passes the validation rules.
     *
     * @return bool true if validation was successful; otherwise, false
     */
    public function success(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * Get validation failed status.
     *
     * @return bool Status
     */
    public function fails(): bool
    {
        return !$this->success();
    }

    /**
     * Clear errors and message.
     */
    public function clear(): void
    {
        $this->errors = [];
    }

    /**
     * Add error message.
     *
     * @param string $message A String providing a short description of the error.
     * @param ?string $field The field name containing the error
     */
    public function addError(string $message, ?string $field = null): void
    {
        $error = new ValidationError($message, $field);
        $this->errors[] = $error;
    }
}
