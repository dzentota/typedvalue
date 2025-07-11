<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Tests\Security;

use dzentota\TypedValue\Examples\EmailAddress;
use dzentota\TypedValue\Examples\PrimaryAccountNumber;
use dzentota\TypedValue\Examples\DateOfBirth;
use dzentota\TypedValue\Examples\SessionId;
use PHPUnit\Framework\TestCase;

class PersistentDataTest extends TestCase
{
    public function testEmailAddressPersistentRepresentation(): void
    {
        $email = EmailAddress::fromNative('test@example.com');
        
        $persistent = $email->getPersistentRepresentation();
        
        $this->assertIsString($persistent);
        $this->assertStringStartsWith('DB_EMAIL_', $persistent);
        $this->assertNotSame('test@example.com', $persistent);
        
        // Test consistency - same email should produce same token
        $email2 = EmailAddress::fromNative('test@example.com');
        $this->assertSame($persistent, $email2->getPersistentRepresentation());
    }

    public function testPrimaryAccountNumberPersistentRepresentation(): void
    {
        $pan = PrimaryAccountNumber::fromNative('4111111111111111');
        
        $persistent = $pan->getPersistentRepresentation();
        
        $this->assertIsString($persistent);
        $this->assertStringStartsWith('ENC_PAN_', $persistent);
        $this->assertNotSame('4111111111111111', $persistent);
        
        // Verify it's base64 encoded
        $encodedPart = substr($persistent, 8); // Remove 'ENC_PAN_' prefix
        $decoded = base64_decode($encodedPart);
        $this->assertSame('4111111111111111', $decoded);
    }

    public function testDateOfBirthPersistentRepresentation(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        $persistent = $dob->getPersistentRepresentation();
        
        $this->assertIsString($persistent);
        $this->assertNotSame('1990-01-01', $persistent);
        
        // Should be a hash (same as logging representation)
        $loggingRep = $dob->getSafeLoggableRepresentation();
        $this->assertSame($loggingRep, $persistent);
    }

    public function testSessionIdPersistentRepresentation(): void
    {
        $sessionId = SessionId::generate();
        
        $persistent = $sessionId->getPersistentRepresentation();
        
        $this->assertIsString($persistent);
        $this->assertNotSame($sessionId->toNative(), $persistent);
        
        // Should be a hash (same as logging representation)
        $loggingRep = $sessionId->getSafeLoggableRepresentation();
        $this->assertSame($loggingRep, $persistent);
    }

    public function testPersistentDataConsistency(): void
    {
        // Test that persistent representations are consistent across instances
        $email1 = EmailAddress::fromNative('user@company.com');
        $email2 = EmailAddress::fromNative('user@company.com');
        
        $this->assertSame(
            $email1->getPersistentRepresentation(),
            $email2->getPersistentRepresentation()
        );
    }

    public function testPersistentDataUniqueness(): void
    {
        // Test that different values produce different persistent representations
        $email1 = EmailAddress::fromNative('user1@company.com');
        $email2 = EmailAddress::fromNative('user2@company.com');
        
        $this->assertNotSame(
            $email1->getPersistentRepresentation(),
            $email2->getPersistentRepresentation()
        );
    }
} 