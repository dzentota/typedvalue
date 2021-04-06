<?php
declare(strict_types=1);

namespace dzentota\TypedValue;

interface Typed
{
    public static function tryParse($value, ?Typed &$typed = null, ?ValidationResult &$result = null): bool;

    /**
     * @return bool
     */
    public function isNull(): bool;

    /**
     * @param Typed $object
     * @return bool
     */
    public function isSame(Typed $object): bool;

    /**
     * @param mixed $native
     */
    public static function fromNative($native): Typed;

    /**
     * @return mixed
     */
    public function toNative();

}
