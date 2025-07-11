<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue\Examples;

use dzentota\TypedValue\Examples\EmailAddress;
use dzentota\TypedValue\Security\SecurityStrategy;
use dzentota\TypedValue\ValidationException;
use PHPUnit\Framework\TestCase;

final class EmailAddressTest extends TestCase
{
    public function test_valid_email_address()
    {
        $email = EmailAddress::fromNative('user@example.com');
        
        $this->assertEquals('user@example.com', $email->toNative());
    }

    public function test_logging_policy_is_tokenize()
    {
        $strategy = EmailAddress::getLoggingSecurityStrategy();
        
        $this->assertEquals(SecurityStrategy::TOKENIZE, $strategy);
    }

    public function test_safe_loggable_representation_creates_token()
    {
        $email = EmailAddress::fromNative('user@example.com');
        
        $token = $email->getSafeLoggableRepresentation();
        
        $this->assertStringStartsWith('EMAIL_', $token);
        $this->assertEquals(22, strlen($token)); // EMAIL_ + 16 chars
    }

    public function test_get_domain()
    {
        $email = EmailAddress::fromNative('user@example.com');
        
        $this->assertEquals('example.com', $email->getDomain());
    }

    public function test_get_local_part()
    {
        $email = EmailAddress::fromNative('user@example.com');
        
        $this->assertEquals('user', $email->getLocalPart());
    }

    public function test_is_from_domain()
    {
        $email = EmailAddress::fromNative('user@example.com');
        
        $this->assertTrue($email->isFromDomain('example.com'));
        $this->assertTrue($email->isFromDomain('EXAMPLE.COM')); // Case insensitive
        $this->assertFalse($email->isFromDomain('other.com'));
    }

    public function test_is_corporate_email()
    {
        $corporateEmail = EmailAddress::fromNative('john@company.com');
        $personalEmail = EmailAddress::fromNative('john@gmail.com');
        
        $this->assertTrue($corporateEmail->isCorporateEmail());
        $this->assertFalse($personalEmail->isCorporateEmail());
    }

    public function test_personal_domains_are_not_corporate()
    {
        $personalDomains = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'aol.com', 'icloud.com', 'mail.com', 'protonmail.com'
        ];
        
        foreach ($personalDomains as $domain) {
            $email = EmailAddress::fromNative("user@{$domain}");
            $this->assertFalse($email->isCorporateEmail(), "Failed for domain: {$domain}");
        }
    }

    public function test_invalid_email_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid email address format');
        
        EmailAddress::fromNative('invalid-email');
    }

    public function test_too_long_email_fails_validation()
    {
        // Create email exactly 255 characters (1 over limit)
        $longLocalPart = str_repeat('a', 243); // 243 chars
        $longEmail = $longLocalPart . '@example.com'; // 243 + 12 = 255 chars
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email address is too long');
        
        EmailAddress::fromNative($longEmail);
    }

    public function test_non_string_email_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Email address must be a string');
        
        EmailAddress::fromNative(123456);
    }

    public function test_tokenization_is_deterministic()
    {
        $email1 = EmailAddress::fromNative('user@example.com');
        $email2 = EmailAddress::fromNative('user@example.com');
        
        $token1 = $email1->getSafeLoggableRepresentation();
        $token2 = $email2->getSafeLoggableRepresentation();
        
        $this->assertEquals($token1, $token2);
    }
} 