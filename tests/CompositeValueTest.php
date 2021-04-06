<?php

// @codingStandardsIgnoreFile
declare(strict_types=1);

namespace tests\dzentota\TypedValue;

use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;
use PHPUnit\Framework\TestCase;

final class CompositeValueTest extends TestCase
{
    public function test_is_null_returns_false()
    {
        $test = CompositeValue::fromNative(['email' => 'foo@bar.com', 'url' => 'https://example.com']);
        $this->assertFalse($test->isNull());
    }

    public function test_is_same_returns_false_when_values_mismatch()
    {
        $test1 = CompositeValue::fromNative(['email' => 'foo@bar.com', 'url' => 'https://example.com']);
        $test2 = CompositeValue::fromNative(['email' => 'baz@bar.com', 'url' => 'https://example.com']);

        $this->assertFalse($test1->isSame($test2));
    }

    public function test_is_same_returns_true_when_values_match()
    {
        $test1 = CompositeValue::fromNative(['email' => 'foo@bar.com', 'url' => 'https://example.com']);
        $test2 = CompositeValue::fromNative(['email' => 'foo@bar.com', 'url' => 'https://example.com']);

        $this->assertTrue($test1->isSame($test2));
    }

    public function test_from_native_throws_exception_when_given_non_array()
    {
        $this->expectException(\InvalidArgumentException::class);
        CompositeValue::fromNative(1000);
    }

    public function test_to_native_returns_original_value_and_null()
    {
        $native = ['email' => 'foo@bar.com', 'url' => 'https://example.com'];
        $expected = ['email' => 'foo@bar.com', 'url' => 'https://example.com', 'option' => null];
        $compositeValue = CompositeValue::fromNative($native);
        $this->assertEquals($expected, $compositeValue->toNative());
    }

    public function test_try_parse_success()
    {
        $native = ['email' => 'foo@bar.com', 'url' => 'https://example.com'];
        $expected = ['email' => 'foo@bar.com', 'url' => 'https://example.com', 'option' => null];
        $isParsed = CompositeValue::tryParse($native, $composite);
        $this->assertTrue($isParsed);
        $this->assertInstanceOf(CompositeValue::class, $composite);
        $this->assertEquals($expected, $composite->toNative());
    }

    public function test_try_parse_fail()
    {
        $native = ['foo' => 'foo@bar.com'];
        $isParsed = CompositeValue::tryParse($native, $composite, $validationResult);
        $this->assertFalse($isParsed);
        $this->assertNull($composite);
        $this->assertInstanceOf(ValidationResult::class, $validationResult);
        $this->assertTrue($validationResult->fails());
    }
}

final class CompositeValue implements Typed
{
    use \dzentota\TypedValue\CompositeValue;

    private Email $email;
    private Url $url;
    private Option $option;
}

class Option implements Typed
{
    use TypedValue;

    public static function validate($value): ValidationResult
    {
        $validation = new ValidationResult();
        if (!($value === null || is_string($value))) {
            $validation->addError('Only strings allowed');
        }
        return $validation;
    }
}

class Email implements Typed
{
    use TypedValue;
    public static function validate($value): ValidationResult
    {
        $validation = new ValidationResult();
        if (false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $validation->addError('Not a valid email');
        }
        return $validation;
    }
}

class Url implements Typed
{
    use TypedValue;
    public static function validate($value): ValidationResult
    {
        $validation = new ValidationResult();
        if (false === filter_var($value, FILTER_VALIDATE_URL)) {
            $validation->addError('Not a valid url');
        }
        return $validation;
    }
}