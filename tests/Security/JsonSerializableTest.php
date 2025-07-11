<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Tests\Security;

use dzentota\TypedValue\Examples\EmailAddress;
use dzentota\TypedValue\Examples\PrimaryAccountNumber;
use dzentota\TypedValue\Examples\User;
use dzentota\TypedValue\Examples\DateOfBirth;
use dzentota\TypedValue\Examples\UserPassword;
use dzentota\TypedValue\Examples\SessionId;
use PHPUnit\Framework\TestCase;

class JsonSerializableTest extends TestCase
{
    public function testEmailAddressJsonSerialization(): void
    {
        $email = EmailAddress::fromNative('user@example.com');
        
        $jsonData = $email->jsonSerialize();
        
        $this->assertIsArray($jsonData);
        $this->assertArrayHasKey('domain', $jsonData);
        $this->assertArrayHasKey('is_corporate', $jsonData);
        $this->assertSame('example.com', $jsonData['domain']);
        $this->assertIsBool($jsonData['is_corporate']);
        
        // Should not contain the actual email address
        $this->assertNotContains('user@example.com', array_values($jsonData));
    }

    public function testPrimaryAccountNumberJsonSerialization(): void
    {
        $pan = PrimaryAccountNumber::fromNative('4111111111111111');
        
        $jsonData = $pan->jsonSerialize();
        
        $this->assertIsArray($jsonData);
        $this->assertArrayHasKey('last_four', $jsonData);
        $this->assertArrayHasKey('brand', $jsonData);
        $this->assertArrayHasKey('type', $jsonData);
        $this->assertSame('1111', $jsonData['last_four']);
        $this->assertSame('Visa', $jsonData['brand']);
        $this->assertSame('card', $jsonData['type']);
        
        // Should not contain the full PAN
        $this->assertNotContains('4111111111111111', array_values($jsonData));
    }

    public function testEmailAddressJsonEncoding(): void
    {
        $email = EmailAddress::fromNative('test@corporate.com');
        
        $json = json_encode($email);
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertSame('corporate.com', $decoded['domain']);
        $this->assertTrue($decoded['is_corporate']);
        
        // Verify the JSON doesn't contain sensitive data
        $this->assertStringNotContainsString('test@corporate.com', $json);
    }

    public function testPrimaryAccountNumberJsonEncoding(): void
    {
        $pan = PrimaryAccountNumber::fromNative('5555555555554444');
        
        $json = json_encode($pan);
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertSame('4444', $decoded['last_four']);
        $this->assertSame('MasterCard', $decoded['brand']);
        $this->assertSame('card', $decoded['type']);
        
        // Verify the JSON doesn't contain sensitive data
        $this->assertStringNotContainsString('5555555555554444', $json);
    }

    public function testUserEntityJsonSerialization(): void
    {
        $email = EmailAddress::fromNative('user@example.com');
        $password = UserPassword::fromNative('SecurePass123!');
        $pan = PrimaryAccountNumber::fromNative('4111111111111111');
        $dob = DateOfBirth::fromNative('1990-01-01');
        $sessionId = SessionId::generate();
        
        $user = new User(1, 'testuser', $email, $password, $pan, $dob, $sessionId);
        
        $jsonData = $user->jsonSerialize();
        
        $this->assertIsArray($jsonData);
        $this->assertArrayHasKey('id', $jsonData);
        $this->assertArrayHasKey('username', $jsonData);
        $this->assertArrayHasKey('email', $jsonData);
        $this->assertArrayHasKey('has_payment_method', $jsonData);
        $this->assertArrayHasKey('payment_method', $jsonData);
        $this->assertArrayHasKey('age_group', $jsonData);
        $this->assertArrayHasKey('session_info', $jsonData);
        
        $this->assertSame(1, $jsonData['id']);
        $this->assertSame('testuser', $jsonData['username']);
        $this->assertTrue($jsonData['has_payment_method']);
        
        // Email should be serialized safely
        $this->assertIsArray($jsonData['email']);
        $this->assertSame('example.com', $jsonData['email']['domain']);
        
        // Payment method should be serialized safely
        $this->assertIsArray($jsonData['payment_method']);
        $this->assertSame('1111', $jsonData['payment_method']['last_four']);
        $this->assertSame('Visa', $jsonData['payment_method']['brand']);
        
        // Age group should be present
        $this->assertIsString($jsonData['age_group']);
        
        // Session info should be safe
        $this->assertIsArray($jsonData['session_info']);
        $this->assertArrayHasKey('id', $jsonData['session_info']);
        $this->assertArrayHasKey('type', $jsonData['session_info']);
    }

    public function testUserEntityJsonEncoding(): void
    {
        $email = EmailAddress::fromNative('sensitive@company.com');
        $password = UserPassword::fromNative('TopSecret123!');
        $user = new User(1, 'testuser', $email, $password);
        
        $json = json_encode($user);
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertSame(1, $decoded['id']);
        $this->assertSame('testuser', $decoded['username']);
        
        // Verify no sensitive data in JSON
        $this->assertStringNotContainsString('sensitive@company.com', $json);
        $this->assertStringNotContainsString('TopSecret123!', $json);
        $this->assertStringNotContainsString('password', $json);
    }

    public function testDifferentCardBrandsJsonSerialization(): void
    {
        $testCards = [
            '4111111111111111' => 'Visa',
            '5555555555554444' => 'MasterCard',
            '378282246310005' => 'American Express',
            '6011111111111117' => 'Discover'
        ];
        
        foreach ($testCards as $cardNumber => $expectedBrand) {
            $pan = PrimaryAccountNumber::fromNative($cardNumber);
            $jsonData = $pan->jsonSerialize();
            
            $this->assertSame($expectedBrand, $jsonData['brand']);
            $this->assertSame(substr((string)$cardNumber, -4), $jsonData['last_four']);
            $this->assertSame('card', $jsonData['type']);
        }
    }

    public function testEmailTypesJsonSerialization(): void
    {
        $testEmails = [
            'user@gmail.com' => false,  // Personal
            'user@company.com' => true,  // Corporate
            'test@yahoo.com' => false,   // Personal
            'employee@business.org' => true  // Corporate
        ];
        
        foreach ($testEmails as $emailAddress => $expectedCorporate) {
            $email = EmailAddress::fromNative($emailAddress);
            $jsonData = $email->jsonSerialize();
            
            $this->assertSame($expectedCorporate, $jsonData['is_corporate']);
            $this->assertSame(substr(strrchr($emailAddress, '@'), 1), $jsonData['domain']);
        }
    }

    public function testJsonSerializationPreventsSensitiveDataLeakage(): void
    {
        // Test that sensitive data is never exposed in JSON
        $email = EmailAddress::fromNative('confidential@secret.com');
        $pan = PrimaryAccountNumber::fromNative('4111111111111111');
        $password = UserPassword::fromNative('UltraSecret123!');
        $user = new User(1, 'testuser', $email, $password, $pan);
        
        $json = json_encode($user);
        
        // None of these sensitive values should appear in JSON
        $this->assertStringNotContainsString('confidential@secret.com', $json);
        $this->assertStringNotContainsString('4111111111111111', $json);
        $this->assertStringNotContainsString('UltraSecret123!', $json);
        $this->assertStringNotContainsString('password', $json);
        
        // But safe representations should be present
        $this->assertStringContainsString('secret.com', $json);
        $this->assertStringContainsString('1111', $json);
        $this->assertStringContainsString('Visa', $json);
    }

    public function testJsonSerializationConsistency(): void
    {
        // Test that JSON serialization is consistent across instances
        $email1 = EmailAddress::fromNative('test@example.com');
        $email2 = EmailAddress::fromNative('test@example.com');
        
        $json1 = json_encode($email1);
        $json2 = json_encode($email2);
        
        $this->assertSame($json1, $json2);
    }

    public function testNestedJsonSerialization(): void
    {
        // Test JSON serialization when objects contain other serializable objects
        $email = EmailAddress::fromNative('user@example.com');
        $password = UserPassword::fromNative('SecurePass123!');
        $pan = PrimaryAccountNumber::fromNative('4111111111111111');
        $user = new User(1, 'testuser', $email, $password, $pan);
        
        $jsonData = $user->jsonSerialize();
        
        // Email should be properly nested - it's already an array from jsonSerialize()
        $emailData = $jsonData['email'];
        $this->assertIsArray($emailData);
        $this->assertArrayHasKey('domain', $emailData);
        $this->assertArrayHasKey('is_corporate', $emailData);
        
        // Payment method should be properly nested - it's already an array from jsonSerialize()
        $paymentData = $jsonData['payment_method'];
        $this->assertIsArray($paymentData);
        $this->assertArrayHasKey('last_four', $paymentData);
        $this->assertArrayHasKey('brand', $paymentData);
        $this->assertArrayHasKey('type', $paymentData);
    }
} 