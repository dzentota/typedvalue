<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace tests\dzentota\TypedValue;

use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use PHPUnit\Framework\TestCase;

final class TypedValueTest extends TestCase
{
    public function test_is_null_returns_false()
    {
        $test = StringValue::fromNative('foo');
        $this->assertFalse($test->isNull());
    }

    public function test_is_same_returns_false_when_values_mismatch()
    {
        $test1 = StringValue::fromNative('foo');
        $test2 = StringValue::fromNative('bar');

        $this->assertFalse($test1->isSame($test2));
    }

    public function test_is_same_returns_true_when_values_match()
    {
        $test1 = StringValue::fromNative('foo');
        $test2 = StringValue::fromNative('foo');

        $this->assertTrue($test1->isSame($test2));
    }

    public function test_from_native_throws_exception_when_given_non_string()
    {
        $this->expectException(\InvalidArgumentException::class);
        StringValue::fromNative(1000);
    }

    public function test_to_native_returns_original_value()
    {
        $native = 'foo';
        $stringValue = StringValue::fromNative($native);
        $this->assertEquals($native, $stringValue->toNative());
    }
}

final class StringValue implements Typed
{
    use TypedValue;

    public static function validate($value): bool
    {
        return is_string($value) && strlen($value) > 0;
    }
}
