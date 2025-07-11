<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue\Examples;

use dzentota\TypedValue\Examples\SessionId;
use dzentota\TypedValue\ValidationException;
use PHPUnit\Framework\TestCase;

final class SessionIdTest extends TestCase
{
    public function test_valid_session_id()
    {
        $sessionId = SessionId::fromNative('abc123def456ghi789jkl012mno345pqr');
        
        $this->assertEquals('abc123def456ghi789jkl012mno345pqr', $sessionId->toNative());
    }

    public function test_logging_policy_is_hash_sha256()
    {
        $policy = SessionId::getLoggingPolicy();
        
        $this->assertTrue($policy->isHashSha256());
    }

    public function test_safe_loggable_representation_creates_hash()
    {
        $sessionId = SessionId::fromNative('abc123def456ghi789jkl012mno345pqr');
        
        $hash = $sessionId->getSafeLoggableRepresentation();
        
        $this->assertEquals(64, strlen($hash)); // SHA256 produces 64-char hex string
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_hashing_is_deterministic()
    {
        $sessionId1 = SessionId::fromNative('abc123def456ghi789jkl012mno345pqr');
        $sessionId2 = SessionId::fromNative('abc123def456ghi789jkl012mno345pqr');
        
        $hash1 = $sessionId1->getSafeLoggableRepresentation();
        $hash2 = $sessionId2->getSafeLoggableRepresentation();
        
        $this->assertEquals($hash1, $hash2);
    }

    public function test_different_session_ids_produce_different_hashes()
    {
        $sessionId1 = SessionId::fromNative('abc123def456ghi789jkl012mno345pqr');
        $sessionId2 = SessionId::fromNative('different123session456id789012345');
        
        $hash1 = $sessionId1->getSafeLoggableRepresentation();
        $hash2 = $sessionId2->getSafeLoggableRepresentation();
        
        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_generate_creates_valid_session_id()
    {
        $sessionId = SessionId::generate();
        
        $this->assertInstanceOf(SessionId::class, $sessionId);
        $this->assertEquals(64, strlen($sessionId->toNative())); // Default length 32 bytes = 64 hex chars
    }

    public function test_generate_with_custom_length()
    {
        $sessionId = SessionId::generate(16); // 16 bytes = 32 hex chars
        
        $this->assertEquals(32, strlen($sessionId->toNative()));
    }

    public function test_appears_expired_for_short_session_ids()
    {
        $shortSessionId = SessionId::fromNative('short123session456'); // 18 chars - valid but short
        $longSessionId = SessionId::fromNative('this_is_a_much_longer_session_id_that_should_not_appear_expired');
        
        $this->assertTrue($shortSessionId->appearsExpired());
        $this->assertFalse($longSessionId->appearsExpired());
    }

    public function test_debug_representation()
    {
        $sessionId = SessionId::fromNative('abc123def456ghi789jkl012mno345pqr');
        
        $debug = $sessionId->getDebugRepresentation();
        
        $this->assertStringStartsWith('abc123de', $debug);
        $this->assertStringContainsString('...', $debug);
        $this->assertEquals(19, strlen($debug)); // 8 chars + ... + 8 chars = 19
    }

    public function test_too_short_session_id_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID must be at least 16 characters long');
        
        SessionId::fromNative('short');
    }

    public function test_too_long_session_id_fails_validation()
    {
        $longSessionId = str_repeat('a', 129);
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID must not exceed 128 characters');
        
        SessionId::fromNative($longSessionId);
    }

    public function test_invalid_characters_fail_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID contains invalid characters');
        
        SessionId::fromNative('abc123!@#$%^&*()def456'); // Contains special chars not allowed
    }

    public function test_non_string_session_id_fails_validation()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session ID must be a string');
        
        SessionId::fromNative(123456789);
    }

    public function test_valid_characters_are_accepted()
    {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_+/=';
        
        $sessionId = SessionId::fromNative($validChars);
        
        $this->assertEquals($validChars, $sessionId->toNative());
    }
} 