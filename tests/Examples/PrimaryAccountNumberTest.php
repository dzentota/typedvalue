<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue\Examples;

use dzentota\TypedValue\Examples\PrimaryAccountNumber;
use dzentota\TypedValue\Security\LoggingPolicy;
use dzentota\TypedValue\ValidationException;
use PHPUnit\Framework\TestCase;

final class PrimaryAccountNumberTest extends TestCase
{
    public function test_valid_visa_card_number()
    {
        // Valid Visa test card number
        $pan = PrimaryAccountNumber::fromNative('4111111111111111');
        
        $this->assertEquals('4111111111111111', $pan->toNative());
        $this->assertEquals('Visa', $pan->getCardBrand());
    }

    public function test_valid_mastercard_number()
    {
        // Valid MasterCard test card number
        $pan = PrimaryAccountNumber::fromNative('5555555555554444');
        
        $this->assertEquals('MasterCard', $pan->getCardBrand());
    }

    public function test_valid_amex_number()
    {
        // Valid American Express test card number
        $pan = PrimaryAccountNumber::fromNative('378282246310005');
        
        $this->assertEquals('American Express', $pan->getCardBrand());
    }

    public function test_logging_policy_is_mask_partial()
    {
        $policy = PrimaryAccountNumber::getLoggingPolicy();
        
        $this->assertTrue($policy->isMaskPartial());
    }

    public function test_safe_loggable_representation_masks_correctly()
    {
        $pan = PrimaryAccountNumber::fromNative('4111111111111111');
        
        $safeRep = $pan->getSafeLoggableRepresentation();
        
        $this->assertEquals('411111******1111', $safeRep);
    }

    public function test_invalid_card_number_fails_validation()
    {
        $this->expectException(ValidationException::class);
        PrimaryAccountNumber::fromNative('1234567890123456'); // Invalid Luhn
    }

    public function test_too_short_number_fails_validation()
    {
        $this->expectException(ValidationException::class);
        PrimaryAccountNumber::fromNative('123456789012'); // Too short
    }

    public function test_too_long_number_fails_validation()
    {
        $this->expectException(ValidationException::class);
        PrimaryAccountNumber::fromNative('12345678901234567890'); // Too long
    }

    public function test_non_numeric_fails_validation()
    {
        $this->expectException(ValidationException::class);
        PrimaryAccountNumber::fromNative('411a111111111111'); // Contains letter
    }

    public function test_unknown_brand_for_invalid_prefix()
    {
        // Use a known valid Diners Club test number (starts with 30, not in our brand detection)
        $pan = PrimaryAccountNumber::fromNative('30569309025904');
        
        $this->assertEquals('Unknown', $pan->getCardBrand());
    }

    public function test_spaces_are_stripped_from_input()
    {
        $pan = PrimaryAccountNumber::fromNative('4111 1111 1111 1111');
        
        $this->assertEquals('4111111111111111', $pan->toNative());
    }
} 