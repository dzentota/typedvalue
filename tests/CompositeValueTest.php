<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace tests\dzentota\TypedValue;

use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
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

    public function test_to_native_returns_original_value()
    {
        $native = ['email' => 'foo@bar.com', 'url' => 'https://example.com'];
        $compositeValue = CompositeValue::fromNative($native);
        $this->assertEquals($native, $compositeValue->toNative());
    }
}

final class CompositeValue implements Typed
{
    use \dzentota\TypedValue\CompositeValue;

    private Email $email;
    private Url $url;
}

class Email implements Typed
{
    use TypedValue;
    public static function validate($value): bool
    {
        return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}

class Url implements Typed
{
    use TypedValue;
    public static function validate($value): bool
    {
        return false !== filter_var($value, FILTER_VALIDATE_URL);
    }
}