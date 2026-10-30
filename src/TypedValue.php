<?php
declare(strict_types=1);

namespace dzentota\TypedValue;

/**
 * @template T
 */
trait TypedValue
{
    /**
     * TypedValue constructor.
     */
    final private function __construct()
    {
    }

    /**
     * @var T
     */
    protected $value;

    /**
     * Implementation of TryParse pattern
     * @link https://docs.microsoft.com/en-us/dotnet/standard/design-guidelines/exceptions-and-performance#try-parse-pattern
     * @param mixed $value Value to parse
     * @param Typed<mixed>|null $typed
     * @param ValidationResult|null $result
     * @param-out static|null $typed
     * @param-out ValidationResult $result
     * @phpstan-assert-if-true =static $typed
     * @return bool
     */
    public static function tryParse($value, ?Typed &$typed = null, ?ValidationResult &$result = null): bool
    {
        $result = static::validate($value);
        if($result->fails()) {
            return false;
        }
        $typed = new static();
        /** @var T $value validated by static::validate() */
        $typed->value = $value;
        return true;
    }

    /**
     * @param mixed $value
     * @return ValidationResult
     */
    abstract public static function validate($value): ValidationResult;

    /**
     * @param mixed $value
     * @return void
     * @throws ValidationException
     */
    public static function assert($value)
    {
        $result = static::validate($value);
        if ($result->fails()) {
            $errors = $result->getErrors();
            $firstError = $errors[0] ?? null;
            $errorMessage = $firstError ? $firstError->getMessage() : 'Validation failed';
            throw new ValidationException($errorMessage, $result);
        }
    }
    /**
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->value === null;
    }

    /**
     * @param Typed<mixed> $object
     * @return bool
     */
    public function isSame(Typed $object): bool
    {
        return ($this->toNative() === $object->toNative());
    }

    /**
     * @param mixed $native
     * @return static
     * @throws ValidationException
     */
    public static function fromNative($native): Typed
    {
        static::assert($native);
        $typedValue = new static();
        /** @var T $native validated by static::assert() */
        $typedValue->value = $native;
        return $typedValue;
    }

    /**
     * @return T
     */
    public function toNative()
    {
        return $this->value;
    }

}
