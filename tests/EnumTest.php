<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace tests\dzentota\TypedValue;

use dzentota\TypedValue\Enum;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\ValidationException;
use PHPUnit\Framework\TestCase;

final class EnumTest extends TestCase
{
    public function test_is_null_returns_false()
    {
        $test = StatusEnum::fromNative(StatusEnum::ONE);
        $this->assertFalse($test->isNull());
    }

    public function test_is_same_returns_false_when_values_mismatch()
    {
        $test1 = StatusEnum::fromNative(StatusEnum::ONE);
        $test2 = StatusEnum::fromNative(StatusEnum::TWO);

        $this->assertFalse($test1->isSame($test2));
    }

    public function test_is_same_returns_true_when_values_match()
    {
        $test1 = StatusEnum::fromNative(StatusEnum::ONE);
        $test2 = StatusEnum::fromNative(StatusEnum::ONE);

        $this->assertTrue($test1->isSame($test2));
    }

    public function test_from_native_throws_exception_when_given_non_string()
    {
        $this->expectException(ValidationException::class);
        StatusEnum::fromNative(1000);
    }

    public function test_to_native_returns_original_value()
    {
        $native = StatusEnum::THREE;
        $stringValue = StatusEnum::fromNative($native);
        $this->assertEquals($native, $stringValue->toNative());
    }

    public function test_static_method_call_returns_typed_instance()
    {
        $expectedValue = StatusEnum::THREE;
        $stringValue = StatusEnum::THREE();
        $this->assertEquals($expectedValue, $stringValue->toNative());
    }
}

final class StatusEnum implements Typed
{
    use Enum;

    const ONE = 1;
    const TWO = 2;
    const THREE = 3;
}
