<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue\Examples;

use dzentota\TypedValue\Examples\CVV;
use dzentota\TypedValue\Security\SecurityStrategy;
use dzentota\TypedValue\ValidationException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class CVVTest extends TestCase
{
    public function test_valid_three_digit_cvv()
    {
        $cvv = CVV::fromNative('123');
        
        $this->assertInstanceOf(CVV::class, $cvv);
        $this->assertFalse($cvv->hasBeenConsumed());
    }

    public function test_valid_four_digit_cvv()
    {
        $cvv = CVV::fromNative('1234');
        
        $this->assertInstanceOf(CVV::class, $cvv);
        $this->assertFalse($cvv->hasBeenConsumed());
    }

    public function test_numeric_cvv_input()
    {
        $cvv = CVV::fromNative(123);
        
        $this->assertInstanceOf(CVV::class, $cvv);
    }

    public function test_logging_policy_is_prohibit()
    {
        $strategy = CVV::getLoggingSecurityStrategy();
        
        $this->assertEquals(SecurityStrategy::PROHIBIT, $strategy);
    }

    public function test_safe_loggable_representation_throws_exception()
    {
        $cvv = CVV::fromNative('123');
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Attempted to access a prohibited value of type');
        
        $cvv->getSafeLoggableRepresentation();
    }

    public function test_cvv_matches_verification()
    {
        $cvv = CVV::fromNative('123');
        
        $this->assertTrue($cvv->matches('123'));
        $this->assertTrue($cvv->hasBeenConsumed());
    }

    public function test_cvv_verification_fails_with_wrong_value()
    {
        $cvv = CVV::fromNative('123');
        
        $this->assertFalse($cvv->matches('456'));
    }

    public function test_cvv_consumed_after_verification()
    {
        $cvv = CVV::fromNative('123');
        
        $cvv->matches('123'); // Consumes the CVV
        
        $this->expectException(LogicException::class);
        $cvv->matches('123'); // Should throw exception
    }

    public function test_get_length_for_three_digit_cvv()
    {
        $cvv = CVV::fromNative('123');
        
        $this->assertEquals(3, $cvv->getLength());
        $this->assertFalse($cvv->isAmexCVV());
    }

    public function test_get_length_for_four_digit_cvv()
    {
        $cvv = CVV::fromNative('1234');
        
        $this->assertEquals(4, $cvv->getLength());
        $this->assertTrue($cvv->isAmexCVV());
    }

    public function test_length_returns_zero_after_consumption()
    {
        $cvv = CVV::fromNative('123');
        
        $this->assertEquals(3, $cvv->getLength());
        
        $cvv->matches('123'); // Consumes the CVV
        
        $this->assertEquals(0, $cvv->getLength());
        $this->assertFalse($cvv->isAmexCVV());
    }

    public function test_verify_and_clear()
    {
        $cvv = CVV::fromNative('123');
        
        $this->assertTrue($cvv->verifyAndClear('123'));
        $this->assertTrue($cvv->hasBeenConsumed());
    }

    public function test_verify_and_clear_fails_with_wrong_value()
    {
        $cvv = CVV::fromNative('123');
        
        $this->assertFalse($cvv->verifyAndClear('456'));
    }

    public function test_verify_and_clear_returns_false_when_already_consumed()
    {
        $cvv = CVV::fromNative('123');
        
        $cvv->matches('123'); // Consume the CVV
        
        $this->assertFalse($cvv->verifyAndClear('123')); // Should return false, not throw
    }

    public function test_too_short_cvv_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CVV must be 3 or 4 digits');
        
        CVV::fromNative('12');
    }

    public function test_too_long_cvv_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CVV must be 3 or 4 digits');
        
        CVV::fromNative('12345');
    }

    public function test_non_numeric_cvv_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CVV must be 3 or 4 digits');
        
        CVV::fromNative('12a');
    }

    public function test_invalid_type_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CVV must be a string or numeric value');
        
        CVV::fromNative([123]);
    }

    public function test_cvv_with_leading_zeros()
    {
        $cvv = CVV::fromNative('001');
        
        $this->assertEquals(3, $cvv->getLength());
        $this->assertTrue($cvv->matches('001'));
    }
} 