<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Tests\Security;

use dzentota\TypedValue\Examples\EmailAddress;
use dzentota\TypedValue\Examples\PrimaryAccountNumber;
use dzentota\TypedValue\Security\UntrustedInput;
use dzentota\TypedValue\Security\UntrustedString;
use PHPUnit\Framework\TestCase;

class UntrustedInputTest extends TestCase
{
    public function testUntrustedStringImplementsInterface(): void
    {
        $untrusted = new UntrustedString('some input');
        
        $this->assertInstanceOf(UntrustedInput::class, $untrusted);
    }

    public function testUntrustedStringStoresRawValue(): void
    {
        $rawValue = 'test@example.com';
        $untrusted = new UntrustedString($rawValue);
        
        $this->assertSame($rawValue, $untrusted->getRawValue());
    }

    public function testUntrustedStringCanStoreAnyType(): void
    {
        $stringValue = 'string value';
        $intValue = 42;
        $arrayValue = ['key' => 'value'];
        $nullValue = null;
        
        $untrustedString = new UntrustedString($stringValue);
        $untrustedInt = new UntrustedString($intValue);
        $untrustedArray = new UntrustedString($arrayValue);
        $untrustedNull = new UntrustedString($nullValue);
        
        $this->assertSame($stringValue, $untrustedString->getRawValue());
        $this->assertSame($intValue, $untrustedInt->getRawValue());
        $this->assertSame($arrayValue, $untrustedArray->getRawValue());
        $this->assertSame($nullValue, $untrustedNull->getRawValue());
    }

    public function testUntrustedInputValidationWorkflow(): void
    {
        // Simulate user input
        $userInput = new UntrustedString('test@example.com');
        
        // Parse into typed value
        $result = EmailAddress::tryParse($userInput->getRawValue(), $email, $errors);
        
        $this->assertTrue($result);
        $this->assertInstanceOf(EmailAddress::class, $email);
        $this->assertSame('test@example.com', $email->toNative());
        $this->assertEmpty($errors->getErrors());
    }

    public function testUntrustedInputValidationFailure(): void
    {
        // Simulate invalid user input
        $userInput = new UntrustedString('invalid-email');
        
        // Attempt to parse into typed value
        $result = EmailAddress::tryParse($userInput->getRawValue(), $email, $errors);
        
        $this->assertFalse($result);
        $this->assertNull($email);
        $this->assertNotEmpty($errors->getErrors());
    }

    public function testUntrustedInputWithPAN(): void
    {
        // Simulate credit card input with spaces
        $userInput = new UntrustedString('4111 1111 1111 1111');
        
        // Parse into PAN (should normalize spaces)
        $result = PrimaryAccountNumber::tryParse($userInput->getRawValue(), $pan, $errors);
        
        $this->assertTrue($result);
        $this->assertInstanceOf(PrimaryAccountNumber::class, $pan);
        $this->assertSame('4111111111111111', $pan->toNative());
        $this->assertEmpty($errors->getErrors());
    }

    public function testUntrustedInputWithInvalidPAN(): void
    {
        // Simulate invalid credit card input
        $userInput = new UntrustedString('1234567890123456');
        
        // Attempt to parse into PAN
        $result = PrimaryAccountNumber::tryParse($userInput->getRawValue(), $pan, $errors);
        
        $this->assertFalse($result);
        $this->assertNull($pan);
        $this->assertNotEmpty($errors->getErrors());
        
        $errorMessages = array_map(function($error) { return $error->getMessage(); }, $errors->getErrors());
        $this->assertContains('Primary Account Number failed Luhn validation', $errorMessages);
    }

    public function testUntrustedInputWorkflowWithMultipleTypes(): void
    {
        $inputs = [
            'email' => new UntrustedString('user@example.com'),
            'pan' => new UntrustedString('4111111111111111'),
            'invalid_email' => new UntrustedString('not-an-email'),
            'invalid_pan' => new UntrustedString('1234')
        ];
        
        $results = [];
        
        // Process email
        if (EmailAddress::tryParse($inputs['email']->getRawValue(), $email, $errors)) {
            $results['email'] = $email;
        } else {
            $results['email_errors'] = $errors;
        }
        
        // Process PAN
        if (PrimaryAccountNumber::tryParse($inputs['pan']->getRawValue(), $pan, $errors)) {
            $results['pan'] = $pan;
        } else {
            $results['pan_errors'] = $errors;
        }
        
        // Process invalid email
        if (EmailAddress::tryParse($inputs['invalid_email']->getRawValue(), $invalidEmail, $errors)) {
            $results['invalid_email'] = $invalidEmail;
        } else {
            $results['invalid_email_errors'] = $errors;
        }
        
        // Process invalid PAN
        if (PrimaryAccountNumber::tryParse($inputs['invalid_pan']->getRawValue(), $invalidPan, $errors)) {
            $results['invalid_pan'] = $invalidPan;
        } else {
            $results['invalid_pan_errors'] = $errors;
        }
        
        $this->assertInstanceOf(EmailAddress::class, $results['email']);
        $this->assertInstanceOf(PrimaryAccountNumber::class, $results['pan']);
        $this->assertArrayHasKey('invalid_email_errors', $results);
        $this->assertArrayHasKey('invalid_pan_errors', $results);
    }

    public function testUntrustedInputPreventsMisuseAtTypeLevel(): void
    {
        // This test demonstrates the pattern where typed values
        // should only accept validated data, not UntrustedInput directly
        
        $untrusted = new UntrustedString('test@example.com');
        
        // This should not be possible in a real system with proper typing
        // but we can test that the raw value is different from processed value
        $this->assertIsString($untrusted->getRawValue());
        
        // The correct workflow requires explicit parsing
        EmailAddress::tryParse($untrusted->getRawValue(), $email, $errors);
        
        $this->assertInstanceOf(EmailAddress::class, $email);
        $this->assertNotSame($untrusted->getRawValue(), $email); // Different types
        $this->assertSame($untrusted->getRawValue(), $email->toNative()); // Same value after validation
    }
} 