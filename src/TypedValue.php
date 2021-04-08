<?php
declare(strict_types=1);

namespace dzentota\TypedValue;

trait TypedValue
{
    /**
     * TypedValue constructor.
     */
    final private function __construct()
    {
    }

    /**
     * @var mixed
     */
    protected $value;
    protected static bool $readOnce = false;

    /**
     * Implementation of TryParse pattern
     * @link https://docs.microsoft.com/en-us/dotnet/standard/design-guidelines/exceptions-and-performance#try-parse-pattern
     * @param mixed $value Value to parse
     * @param Typed|null $typed
     * @param ValidationResult|null $result
     * @return bool
     */
    public static function tryParse($value, ?Typed &$typed = null, ?ValidationResult &$result = null): bool
    {
        if (static::$readOnce && ($value === null || $value === '')) {
            throw new \LogicException('Empty values are not allowed');
        }
        $result = static::validate($value);
        if($result->fails()) {
            return false;
        }
        $typed = new static();
        $typed->value = $value;
        return true;
    }

    /**
     * @param $value
     * @return ValidationResult
     */
    abstract public static function validate($value): ValidationResult;

    /**
     * @param $value
     */
    public static function assert($value)
    {
        $result = static::validate($value);
        if ($result->fails()) {
            throw new ValidationException(sprintf('"%s" cannot be created from "%s"', get_called_class(), $value), $result);
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
     * @param Typed $object
     * @return bool
     */
    public function isSame(Typed $object): bool
    {
        return ($this->toNative() === $object->toNative());
    }

    /**
     * @param mixed $native
     * @return Typed|static
     */
    public static function fromNative($native): Typed
    {
        static::assert($native);
        $typedValue = new static();
        $typedValue->value = $native;
        return $typedValue;
    }

    /**
     * @return mixed
     */
    public function toNative()
    {
        $value = $this->value;
        if (static::$readOnce) {
            if ($this->value === null) {
                throw new \DomainException('Value has already been consumed');
            }
            $this->value = null;
        }
        return $value;
    }

}
