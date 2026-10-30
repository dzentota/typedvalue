<?php
declare(strict_types=1);

namespace tests\dzentota\TypedValue\StaticAnalysis;

use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;

/**
 * @implements Typed<string>
 */
final class StringFixture implements Typed
{
    /** @use TypedValue<string> */
    use TypedValue;

    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!is_string($value)) {
            $result->addError('Must be a string');
        }
        return $result;
    }
}
