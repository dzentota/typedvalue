<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace tests\dzentota\TypedValue;

use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationException;
use dzentota\TypedValue\ValidationResult;
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
        $this->expectException(ValidationException::class);
        StringValue::fromNative(1000);
    }

    public function test_to_native_returns_original_value()
    {
        $native = 'foo';
        $stringValue = StringValue::fromNative($native);
        $this->assertEquals($native, $stringValue->toNative());
    }

    public function test_try_parse_success()
    {
        $isParsed = StringValue::tryParse('foo', $typed);
        $this->assertTrue($isParsed);
        $this->assertInstanceOf(StringValue::class, $typed);
    }

    public function test_try_parse_fail()
    {
        $isParsed = StringValue::tryParse(false, $typed, $validationResult);
        $this->assertFalse($isParsed);
        $this->assertNull($typed);
        $this->assertInstanceOf(ValidationResult::class, $validationResult);
        $this->assertTrue($validationResult->fails());
    }

    public function test_read_once_type_throws_exception_on_empty_value()
    {
        $this->expectException(\LogicException::class);
        SecretValue::fromNative('');
    }
    public function test_read_once_type_throws_exception_on_null_value()
    {
        $this->expectException(\LogicException::class);
        SecretValue::fromNative(null);
    }

    public function test_read_once_type_throws_exception_when_read_twice()
    {
        $secret = SecretValue::fromNative('secret');
        $this->assertEquals('secret', $secret->toNative());
        $this->expectException(\DomainException::class);
        $secret->toNative();
    }
}

class StringValue implements Typed
{
    use TypedValue;

    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!is_string($value) || strlen($value) <= 0) {
            $result->addError('Only not empty strings are allowed');
        }
        return $result;
    }
}

class SecretValue extends StringValue
{
    protected static bool $readOnce = true;
}
