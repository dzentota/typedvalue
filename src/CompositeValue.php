<?php

declare(strict_types=1);

namespace dzentota\TypedValue;

trait CompositeValue
{
    private static $fieldDefinitions;

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

    /**
     * @return array
     */
    public function toNative(): array
    {
        return array_map(function (Typed $typedValue) {
            return $typedValue->toNative();
        }, $this->propertiesToArray());
    }

    public static function fromNative($value): Typed
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf("Array expected, %s given", gettype($value)));
        }
        $reflectionClass = new \ReflectionClass(static::class);
        $object = $reflectionClass->newInstanceWithoutConstructor();
        $fields = static::getFields();
        $ignored = array_diff_key($value, $fields);
        if (!empty($ignored)) {
            throw new \InvalidArgumentException("Unknown field(s): " . implode(', ', array_keys($ignored)));
        }
        foreach ($fields as $fieldName => $fieldType) {
            $object->$fieldName = $fieldType::fromNative($value[$fieldName]?? null);
        }
        if (is_callable([static::class , 'validateProperties'])) {
            static::validateProperties();
        }
        return $object;
    }

    public static function tryParse($value, ?Typed &$typed): bool
    {
        $reflectionClass = new \ReflectionClass(static::class);
        $typed = $reflectionClass->newInstanceWithoutConstructor();
        $fields = static::getFields();
        $ignored = array_diff_key($value, $fields);
        if (!empty($ignored)) {
            return false;
        }
        foreach ($fields as $fieldName => $fieldType) {
            try {
                $typed->$fieldName = $fieldType::fromNative($value[$fieldName]);
            } catch (\Exception $exception) {
                return false;
            }
        }
        if (is_callable([static::class , 'validateProperties'])) {
            try {
                static::validateProperties();
            } catch (\Exception $exception) {
                return false;
            }
        }
        return true;
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
            } else {
                throw new \DomainException('Class properties must be Typed');
            }
        }
        self::$fieldDefinitions = $result;
        return $result;
    }
}
