<?php

declare(strict_types=1);

namespace dzentota\TypedValue;

use ReflectionException;

trait CompositeValue
{
    private static array $fieldDefinitions = [];
    private static bool $ignoreUnknownFields = true;

    /**
     * @return bool
     */
    public function isNull(): bool
    {
        $subValues = $this->propertiesToArray();

        foreach ($subValues as $value) {
            if (!$value->isNull()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Typed $object
     * @return bool
     */
    public function isSame(Typed $object): bool
    {
        return ($this->toNative() == $object->toNative());
    }

    public function toNative(): array
    {
        return array_map(function (Typed $typedValue) {
            return $typedValue->isNull()? $this->defaults(get_class($typedValue)) : $typedValue->toNative();
        }, $this->propertiesToArray());
    }

    /**
     * @param string $class
     * @return mixed
     */
    protected function defaults(string $class)
    {
        return null;
    }

    /**
     * @param $value
     * @return Typed|static
     * @throws ReflectionException
     */
    public static function fromNative($value): Typed
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf("Array expected, %s given", gettype($value)));
        }
        $reflectionClass = new \ReflectionClass(static::class);
        $composite = $reflectionClass->newInstanceWithoutConstructor();
        $fields = static::getFields();
        $ignored = array_diff_key($value, $fields);
        if (!static::$ignoreUnknownFields && count($ignored)) {
            throw new \InvalidArgumentException("Unknown field(s): " . implode(', ', array_keys($ignored)));
        }
        foreach ($fields as $fieldName => $fieldType) {
            $composite->$fieldName = $fieldType::fromNative($value[$fieldName]?? null);
        }
        $result = static::validateProperties($composite);
        if ($result->fails()) {
            throw new ValidationException(sprintf('"%s" cannot be created from "%s"', get_called_class(), $value), $result);
        }
        return $composite;
    }

    /**
     * @param Typed $value
     * @return ValidationResult
     */
    public static function validateProperties(Typed $value): ValidationResult
    {
        return new ValidationResult();
    }

    public static function tryParse($value, ?Typed &$typed = null, ?ValidationResult &$result = null): bool
    {
        $result = new ValidationResult();
        $fields = static::getFields();
        $ignored = array_diff_key($value, $fields);
        if (!static::$ignoreUnknownFields && count($ignored)) {
            return false;
        }
        $reflectionClass = new \ReflectionClass(static::class);
        $typed = $reflectionClass->newInstanceWithoutConstructor();

        foreach ($fields as $fieldName => $fieldType) {
            if ($fieldType::tryParse($value[$fieldName]?? null, $typedValue, $res)) {
                $typed->$fieldName = $typedValue;
            } else {
                foreach ($res->getErrors() as $error) {
                    /**
                     * @var ValidationError $error
                     */
                    $result->addError($error->getMessage(), $fieldName);
                }
            }
        }
        $res = static::validateProperties($typed);
        if ($res->fails()) {
            foreach ($res->getErrors() as $error) {
                /**
                 * @var ValidationError $error
                 */
                $result->addError($error->getMessage(), $error->getField());
            }
        }
        return $result->success() || ($typed = null);
    }

    /**
     * @return TypedValue[]
     */
    private function propertiesToArray(): array
    {
        $properties =  get_object_vars($this);
        $result = [];
        foreach ($properties as $property => $value) {
            if ($value instanceof Typed) {
                $result[$property] = $value;
            }
        }
        return $result;
    }

    /**
     * @return Typed[]
     */
    private static function getFields(): array
    {
        if (self::$fieldDefinitions) {
            return self::$fieldDefinitions;
        }
        $reflectionClass = new \ReflectionClass(static::class);
        $result = [];
        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $type = $property->getType();
            if ($type && !$type->isBuiltin() && (in_array(Typed::class, class_implements($type->getName())))) {
                $result[$property->name] = $type->getName();
            }
        }
        self::$fieldDefinitions = $result;
        return $result;
    }
}
