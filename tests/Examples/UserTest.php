<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Tests\Examples;

use dzentota\TypedValue\Examples\User;
use dzentota\TypedValue\Examples\EmailAddress;
use dzentota\TypedValue\Examples\UserPassword;
use dzentota\TypedValue\Examples\PrimaryAccountNumber;
use dzentota\TypedValue\Examples\DateOfBirth;
use dzentota\TypedValue\Examples\SessionId;
use PHPUnit\Framework\TestCase;
use JsonSerializable;

class UserTest extends TestCase
{
    private function createTestUser(): User
    {
        $email = EmailAddress::fromNative('user@example.com');
        $password = UserPassword::fromNative('SecurePass123!');
        $creditCard = PrimaryAccountNumber::fromNative('4111111111111111');
        $dateOfBirth = DateOfBirth::fromNative('1990-01-01');
        $sessionId = SessionId::generate();
        
        return new User(1, 'testuser', $email, $password, $creditCard, $dateOfBirth, $sessionId);
    }

    public function testUserImplementsJsonSerializable(): void
    {
        $user = $this->createTestUser();
        
        $this->assertInstanceOf(JsonSerializable::class, $user);
    }

    public function testUserConstruction(): void
    {
        $email = EmailAddress::fromNative('user@example.com');
        $password = UserPassword::fromNative('SecurePass123!');
        
        $user = new User(1, 'testuser', $email, $password);
        
        $this->assertSame(1, $user->getId());
        $this->assertSame('testuser', $user->getUsername());
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($password, $user->getPassword());
        $this->assertNull($user->getCreditCard());
        $this->assertNull($user->getDateOfBirth());
        $this->assertInstanceOf(SessionId::class, $user->getSessionId());
    }

    public function testUserWithAllFields(): void
    {
        $user = $this->createTestUser();
        
        $this->assertSame(1, $user->getId());
        $this->assertSame('testuser', $user->getUsername());
        $this->assertInstanceOf(EmailAddress::class, $user->getEmail());
        $this->assertInstanceOf(UserPassword::class, $user->getPassword());
        $this->assertInstanceOf(PrimaryAccountNumber::class, $user->getCreditCard());
        $this->assertInstanceOf(DateOfBirth::class, $user->getDateOfBirth());
        $this->assertInstanceOf(SessionId::class, $user->getSessionId());
    }

    public function testUserJsonSerialization(): void
    {
        $user = $this->createTestUser();
        
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
        $this->assertIsString($jsonData['age_group']);
        $this->assertIsArray($jsonData['session_info']);
    }

    public function testUserJsonSerializationHidesSensitiveData(): void
    {
        $user = $this->createTestUser();
        
        $json = json_encode($user);
        
        // Should not contain sensitive data
        $this->assertStringNotContainsString('user@example.com', $json);
        $this->assertStringNotContainsString('SecurePass123!', $json);
        $this->assertStringNotContainsString('4111111111111111', $json);
        $this->assertStringNotContainsString('1990-01-01', $json);
        $this->assertStringNotContainsString('password', $json);
        
        // Should contain safe representations
        $this->assertStringContainsString('example.com', $json);
        $this->assertStringContainsString('1111', $json);
        $this->assertStringContainsString('Visa', $json);
    }

    public function testUserPersistentData(): void
    {
        $user = $this->createTestUser();
        
        $persistentData = $user->getPersistentData();
        
        $this->assertIsArray($persistentData);
        $this->assertArrayHasKey('id', $persistentData);
        $this->assertArrayHasKey('username', $persistentData);
        $this->assertArrayHasKey('email', $persistentData);
        $this->assertArrayHasKey('password_hash', $persistentData);
        $this->assertArrayHasKey('credit_card', $persistentData);
        $this->assertArrayHasKey('date_of_birth', $persistentData);
        $this->assertArrayHasKey('session_id', $persistentData);
        
        $this->assertSame(1, $persistentData['id']);
        $this->assertSame('testuser', $persistentData['username']);
        
        // All sensitive data should be transformed
        $this->assertNotSame('user@example.com', $persistentData['email']);
        $this->assertNotSame('4111111111111111', $persistentData['credit_card']);
        $this->assertNotSame('1990-01-01', $persistentData['date_of_birth']);
        
        // Check that transformations happened
        $this->assertStringStartsWith('DB_EMAIL_', $persistentData['email']);
        $this->assertStringStartsWith('ENC_PAN_', $persistentData['credit_card']);
        $this->assertStringStartsWith('SHA256:', $persistentData['date_of_birth']);
        $this->assertStringStartsWith('SHA256:', $persistentData['session_id']);
    }

    public function testUserReportData(): void
    {
        $user = $this->createTestUser();
        
        $reportData = $user->getReportData();
        
        $this->assertIsArray($reportData);
        $this->assertArrayHasKey('id', $reportData);
        $this->assertArrayHasKey('username_length', $reportData);
        $this->assertArrayHasKey('email_domain', $reportData);
        $this->assertArrayHasKey('is_corporate_email', $reportData);
        $this->assertArrayHasKey('age', $reportData);
        $this->assertArrayHasKey('age_group', $reportData);
        $this->assertArrayHasKey('has_payment_method', $reportData);
        $this->assertArrayHasKey('payment_brand', $reportData);
        $this->assertArrayHasKey('password_strength', $reportData);
        $this->assertArrayHasKey('session_duration', $reportData);
        
        $this->assertSame(1, $reportData['id']);
        $this->assertSame(8, $reportData['username_length']); // 'testuser' length
        $this->assertSame('example.com', $reportData['email_domain']);
        $this->assertIsBool($reportData['is_corporate_email']);
        $this->assertIsInt($reportData['age']);
        $this->assertIsString($reportData['age_group']);
        $this->assertTrue($reportData['has_payment_method']);
        $this->assertSame('Visa', $reportData['payment_brand']);
        $this->assertIsInt($reportData['password_strength']);
        $this->assertIsInt($reportData['session_duration']);
    }

    public function testUserWithoutOptionalFields(): void
    {
        $email = EmailAddress::fromNative('user@example.com');
        $password = UserPassword::fromNative('SecurePass123!');
        $user = new User(1, 'testuser', $email, $password);
        
        $jsonData = $user->jsonSerialize();
        
        $this->assertFalse($jsonData['has_payment_method']);
        $this->assertNull($jsonData['payment_method']);
        $this->assertNull($jsonData['age_group']);
        
        $reportData = $user->getReportData();
        
        $this->assertFalse($reportData['has_payment_method']);
        $this->assertNull($reportData['payment_brand']);
        $this->assertNull($reportData['age']);
        $this->assertNull($reportData['age_group']);
    }

    public function testUserEmailUpdate(): void
    {
        $user = $this->createTestUser();
        $newEmail = EmailAddress::fromNative('newemail@example.com');
        
        $user->updateEmail($newEmail);
        
        $this->assertSame($newEmail, $user->getEmail());
        $this->assertSame('newemail@example.com', $user->getEmail()->toNative());
    }

    public function testUserPasswordUpdate(): void
    {
        $user = $this->createTestUser();
        $newPassword = UserPassword::fromNative('NewSecurePass456!');
        
        $user->updatePassword($newPassword);
        
        $this->assertSame($newPassword, $user->getPassword());
    }

    public function testUserCreditCardUpdate(): void
    {
        $user = $this->createTestUser();
        $newCreditCard = PrimaryAccountNumber::fromNative('5555555555554444');
        
        $user->updateCreditCard($newCreditCard);
        
        $this->assertSame($newCreditCard, $user->getCreditCard());
        $this->assertSame('5555555555554444', $user->getCreditCard()->toNative());
    }

    public function testUserSecurityIsolation(): void
    {
        // Test that different users have different security representations
        $email1 = EmailAddress::fromNative('user1@example.com');
        $email2 = EmailAddress::fromNative('user2@example.com');
        $password1 = UserPassword::fromNative('SecurePass123!');
        $password2 = UserPassword::fromNative('SecurePass456!');
        
        $user1 = new User(1, 'user1', $email1, $password1);
        $user2 = new User(2, 'user2', $email2, $password2);
        
        $persistent1 = $user1->getPersistentData();
        $persistent2 = $user2->getPersistentData();
        
        $this->assertNotSame($persistent1['email'], $persistent2['email']);
        $this->assertNotSame($persistent1['password_hash'], $persistent2['password_hash']);
        $this->assertNotSame($persistent1['session_id'], $persistent2['session_id']);
    }

    public function testUserCompleteWorkflow(): void
    {
        // Test complete user workflow from creation to all representations
        $user = $this->createTestUser();
        
        // JSON for API
        $apiData = json_encode($user);
        $this->assertIsString($apiData);
        
        // Persistent data for database
        $dbData = $user->getPersistentData();
        $this->assertIsArray($dbData);
        
        // Report data for analytics
        $reportData = $user->getReportData();
        $this->assertIsArray($reportData);
        
        // Verify no sensitive data leakage
        $this->assertStringNotContainsString('user@example.com', $apiData);
        $this->assertStringNotContainsString('SecurePass123!', $apiData);
        $this->assertStringNotContainsString('4111111111111111', $apiData);
        $this->assertStringNotContainsString('1990-01-01', $apiData);
        
        // Verify transformed data is present
        $this->assertStringStartsWith('DB_EMAIL_', $dbData['email']);
        $this->assertStringStartsWith('ENC_PAN_', $dbData['credit_card']);
        $this->assertStringStartsWith('SHA256:', $dbData['date_of_birth']);
        
        // Verify anonymized data is useful
        $this->assertIsInt($reportData['age']);
        $this->assertIsString($reportData['age_group']);
        $this->assertSame('example.com', $reportData['email_domain']);
        $this->assertSame('Visa', $reportData['payment_brand']);
    }

    public function testUserWithNullableFields(): void
    {
        $email = EmailAddress::fromNative('user@example.com');
        $password = UserPassword::fromNative('SecurePass123!');
        $user = new User(1, 'testuser', $email, $password, null, null, null);
        
        $this->assertNull($user->getCreditCard());
        $this->assertNull($user->getDateOfBirth());
        $this->assertInstanceOf(SessionId::class, $user->getSessionId()); // Auto-generated
        
        $persistentData = $user->getPersistentData();
        $this->assertNull($persistentData['credit_card']);
        $this->assertNull($persistentData['date_of_birth']);
        $this->assertIsString($persistentData['session_id']);
    }
} 