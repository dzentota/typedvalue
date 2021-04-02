<?php
declare(strict_types=1);

namespace dzentota\TypedValue;

use Respect\Validation\Validatable;
use Respect\Validation\Validator;

trait Enum
{
    use TypedValue;

    private static ?array $constantsCache = null;

    /**
     * @return bool
     */
    public function isNull(): bool
    {
        return false;
    }

    /**
     * @param $name
     * @param $arguments
     */
    public static function __callStatic($name, $arguments)
    {
        return static::fromNative(constant(get_called_class() . '::' . $name));
    }

    public static function validate($value): bool
    {
        return in_array($value, static::constantValues());
    }

    /**
     * @return array
     */
    private static function constantValues(): array
    {
        $constants = static::constants();
        return array_values($constants);
    }

    /**
     * @return array
     */
    private static function constantKeys(): array
    {
        $constants = static::constants();
        return array_keys($constants);
    }

    /**
     * @return array
     */
    private static function constants(): array
    {
        if (static::$constantsCache !== null) {
            return static::$constantsCache;
        }

        $reflect = new \ReflectionClass(get_called_class());
        return static::$constantsCache = $reflect->getConstants();
    }
}
