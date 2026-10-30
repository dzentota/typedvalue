<?php
declare(strict_types=1);

namespace dzentota\TypedValue;

/**
 * @template-covariant T
 */
interface Typed
{
    /**
     * @param mixed $value
     * @param Typed<mixed>|null $typed
     * @param ValidationResult|null $result
     * @param-out static|null $typed
     * @param-out ValidationResult|null $result
     * @phpstan-assert-if-true =static $typed
     */
    public static function tryParse($value, ?Typed &$typed = null, ?ValidationResult &$result = null): bool;

    /**
     * @return bool
     */
    public function isNull(): bool;

    /**
     * @param Typed<mixed> $object
     * @return bool
     */
    public function isSame(Typed $object): bool;

    /**
     * @param mixed $native
     * @return static
     */
    public static function fromNative($native): Typed;

    /**
     * @return T
     */
    public function toNative();

}
