<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue\Security;

use dzentota\TypedValue\Security\ReadOnce;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ReadOnceTest extends TestCase
{
    public function test_value_can_be_read_once()
    {
        $readOnceValue = TestReadOnceValue::fromNative('secret');
        
        $this->assertFalse($readOnceValue->hasBeenConsumed());
        $this->assertEquals('secret', $readOnceValue->getValue());
        $this->assertTrue($readOnceValue->hasBeenConsumed());
    }

    public function test_second_read_throws_exception()
    {
        $readOnceValue = TestReadOnceValue::fromNative('secret');
        
        $readOnceValue->getValue(); // First read
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value of type ' . TestReadOnceValue::class . ' has already been consumed');
        
        $readOnceValue->getValue(); // Second read should throw
    }

    public function test_to_native_uses_read_once_behavior()
    {
        $readOnceValue = TestReadOnceValue::fromNative('secret');
        
        $this->assertEquals('secret', $readOnceValue->toNative());
        
        $this->expectException(LogicException::class);
        $readOnceValue->toNative(); // Second call should throw
    }

    public function test_has_been_consumed_doesnt_consume_value()
    {
        $readOnceValue = TestReadOnceValue::fromNative('secret');
        
        $this->assertFalse($readOnceValue->hasBeenConsumed());
        $this->assertFalse($readOnceValue->hasBeenConsumed()); // Can call multiple times
        
        $readOnceValue->getValue();
        
        $this->assertTrue($readOnceValue->hasBeenConsumed());
        $this->assertTrue($readOnceValue->hasBeenConsumed()); // Can call multiple times after consumption
    }
}

class TestReadOnceValue implements Typed
{
    use TypedValue, ReadOnce {
        ReadOnce::toNative insteadof TypedValue;
    }

    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!is_string($value)) {
            $result->addError('Value must be a string');
        }
        return $result;
    }
} 