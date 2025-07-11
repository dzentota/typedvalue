<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue\Examples;

use dzentota\TypedValue\Examples\UserPassword;
use dzentota\TypedValue\Security\LoggingPolicy;
use dzentota\TypedValue\ValidationException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class UserPasswordTest extends TestCase
{
    public function test_valid_strong_password()
    {
        $password = UserPassword::fromNative('StrongP@ssw0rd123');
        
        $this->assertInstanceOf(UserPassword::class, $password);
        $this->assertFalse($password->hasBeenConsumed());
    }

    public function test_logging_policy_is_prohibit()
    {
        $policy = UserPassword::getLoggingPolicy();
        
        $this->assertTrue($policy->isProhibit());
    }

    public function test_safe_loggable_representation_throws_exception()
    {
        $password = UserPassword::fromNative('StrongP@ssw0rd123');
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Attempted to log a prohibited value of type');
        
        $password->getSafeLoggableRepresentation();
    }

    public function test_password_can_be_hashed()
    {
        $password = UserPassword::fromNative('StrongP@ssw0rd123');
        
        $hash = $password->hash();
        
        $this->assertTrue(password_verify('StrongP@ssw0rd123', $hash));
        $this->assertTrue($password->hasBeenConsumed());
    }

    public function test_password_verification_works()
    {
        $password = UserPassword::fromNative('StrongP@ssw0rd123');
        $hash = password_hash('StrongP@ssw0rd123', PASSWORD_DEFAULT);
        
        $this->assertTrue($password->matches($hash));
        $this->assertTrue($password->hasBeenConsumed());
    }

    public function test_password_verification_fails_with_wrong_hash()
    {
        $password = UserPassword::fromNative('StrongP@ssw0rd123');
        $wrongHash = password_hash('WrongPassword', PASSWORD_DEFAULT);
        
        $this->assertFalse($password->matches($wrongHash));
    }

    public function test_password_consumed_after_use()
    {
        $password = UserPassword::fromNative('StrongP@ssw0rd123');
        
        $password->hash(); // Consumes the password
        
        $this->expectException(LogicException::class);
        $password->hash(); // Should throw exception
    }

    public function test_password_strength_calculation()
    {
        $weakPassword = UserPassword::fromNative('Password123!');
        $strongPassword = UserPassword::fromNative('V3ryStr0ng!P@ssw0rd2024#');
        
        $weakStrength = $weakPassword->getStrength();
        $strongStrength = $strongPassword->getStrength();
        
        $this->assertGreaterThan(0, $weakStrength);
        $this->assertGreaterThan($weakStrength, $strongStrength);
        $this->assertLessThanOrEqual(100, $strongStrength);
    }

    public function test_strength_returns_zero_after_consumption()
    {
        $password = UserPassword::fromNative('StrongP@ssw0rd123');
        
        $this->assertGreaterThan(0, $password->getStrength());
        
        $password->hash(); // Consumes the password
        
        $this->assertEquals(0, $password->getStrength());
    }

    /**
     * @dataProvider invalidPasswordProvider
     */
    public function test_invalid_passwords_fail_validation(string $invalidPassword, string $expectedError)
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedError);
        
        UserPassword::fromNative($invalidPassword);
    }

    public function invalidPasswordProvider(): array
    {
        return [
            ['short', 'Password must be at least 8 characters long'],
            ['nouppercase123!', 'Password must contain at least one uppercase letter'],
            ['NOLOWERCASE123!', 'Password must contain at least one lowercase letter'],
            ['NoNumbers!', 'Password must contain at least one number'],
            ['NoSpecialChars123', 'Password must contain at least one special character'],
        ];
    }

    public function test_non_string_password_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Password must be a string');
        
        UserPassword::fromNative(123456789);
    }
} 