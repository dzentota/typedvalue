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

    public static function tryParse($value, ?Typed &$typed): bool
    {
        if(!static::validate($value)) {
            return false;
        }
        $typed = new static();
        $typed->value = $value;
        return true;
    }

    abstract public static function validate($value): bool;

    public static function assert($value)
    {
        if (!static::validate($value)) {
            throw new \InvalidArgumentException(sprintf('"%s" type cannot be created from "%s"', get_called_class(), $value));
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

    public static function fromNative($native): Typed
    {
        static::assert($native);
        $typedValue = new static();
        $typedValue->value = $native;
        return $typedValue;
    }

    public function toNative()
    {
        return $this->value;
    }

}
