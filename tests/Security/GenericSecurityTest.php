<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Tests\Security;

use dzentota\TypedValue\Security\GenericSecurityTrait;
use dzentota\TypedValue\Security\SecurityContext;
use dzentota\TypedValue\Security\SecurityPolicy;
use dzentota\TypedValue\Security\SecurityPolicyProvider;
use dzentota\TypedValue\Security\SecurityStrategy;
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Test the generic security system.
 */
final class GenericSecurityTest extends TestCase
{
    public function testSecurityContextCreation(): void
    {
        $logging = SecurityContext::LOGGING;
        $persistence = SecurityContext::PERSISTENCE;
        $reporting = SecurityContext::REPORTING;
        $serialization = SecurityContext::SERIALIZATION;

        $this->assertTrue($logging->isLogging());
        $this->assertTrue($persistence->isPersistence());
        $this->assertTrue($reporting->isReporting());
        $this->assertTrue($serialization->isSerialization());

        $this->assertFalse($logging->isPersistence());
        $this->assertFalse($persistence->isLogging());
    }

    public function testSecurityStrategyCreation(): void
    {
        $prohibit = SecurityStrategy::PROHIBIT;
        $mask = SecurityStrategy::MASK_PARTIAL;
        $hash = SecurityStrategy::HASH_SHA256;
        $tokenize = SecurityStrategy::TOKENIZE;
        $encrypt = SecurityStrategy::ENCRYPT;
        $plaintext = SecurityStrategy::PLAINTEXT;

        $this->assertTrue($prohibit->isProhibit());
        $this->assertTrue($mask->isMaskPartial());
        $this->assertTrue($hash->isHashSha256());
        $this->assertTrue($tokenize->isTokenize());
        $this->assertTrue($encrypt->isEncrypt());
        $this->assertTrue($plaintext->isPlaintext());
    }

    public function testSecurityPolicyFluentInterface(): void
    {
        $policy = SecurityPolicy::create()
            ->logging(SecurityStrategy::HASH_SHA256)
            ->persistence(SecurityStrategy::ENCRYPT)
            ->reporting(SecurityStrategy::PLAINTEXT)
            ->serialization(SecurityStrategy::TOKENIZE)
            ->build();

        $this->assertTrue($policy->getStrategy(SecurityContext::LOGGING)->isHashSha256());
        $this->assertTrue($policy->getStrategy(SecurityContext::PERSISTENCE)->isEncrypt());
        $this->assertTrue($policy->getStrategy(SecurityContext::REPORTING)->isPlaintext());
        $this->assertTrue($policy->getStrategy(SecurityContext::SERIALIZATION)->isTokenize());
    }

    public function testSecurityPolicyPresets(): void
    {
        $secure = SecurityPolicy::secure();
        $prohibited = SecurityPolicy::prohibited();
        $pii = SecurityPolicy::pii();
        $financial = SecurityPolicy::financial();
        $public = SecurityPolicy::public();

        // Test secure preset
        $this->assertTrue($secure->getStrategy(SecurityContext::LOGGING)->isHashSha256());
        $this->assertTrue($secure->getStrategy(SecurityContext::PERSISTENCE)->isEncrypt());

        // Test prohibited preset
        $this->assertTrue($prohibited->getStrategy(SecurityContext::LOGGING)->isProhibit());
        $this->assertTrue($prohibited->getStrategy(SecurityContext::PERSISTENCE)->isEncrypt());

        // Test public preset
        $this->assertTrue($public->getStrategy(SecurityContext::LOGGING)->isPlaintext());
        $this->assertTrue($public->getStrategy(SecurityContext::PERSISTENCE)->isPlaintext());
    }

    public function testGenericSecurityTraitWithTestClass(): void
    {
        $testValue = TestSecureValue::fromNative('sensitive-data-123');

        // Test logging context
        $logged = $testValue->getSafeLoggableRepresentation();
        $this->assertStringStartsWith('SHA256:', $logged);
        $this->assertStringNotContainsString('sensitive-data-123', $logged);

        // Test persistence context
        $persistent = $testValue->getPersistentRepresentation();
        $this->assertStringStartsWith('ENC_', $persistent);
        $this->assertStringNotContainsString('sensitive-data-123', $persistent);

        // Test reporting context
        $report = $testValue->getAnonymizedReportValue();
        $this->assertSame('sensitive-data-123', $report); // Plaintext for reporting

        // Test serialization context
        $serialized = $testValue->getSecureSerializationValue();
        $this->assertStringStartsWith('TOKEN_', $serialized);
        $this->assertStringNotContainsString('sensitive-data-123', $serialized);
    }

    public function testGenericSecurityTraitWithDirectContextCall(): void
    {
        $testValue = TestSecureValue::fromNative('test-data');

        // Test direct context application
        $hashed = $testValue->applySecurityPolicy(SecurityContext::LOGGING);
        $this->assertStringStartsWith('SHA256:', $hashed);

        $encrypted = $testValue->applySecurityPolicy(SecurityContext::PERSISTENCE);
        $this->assertStringStartsWith('ENC_', $encrypted);

        $plaintext = $testValue->applySecurityPolicy(SecurityContext::REPORTING);
        $this->assertSame('test-data', $plaintext);
    }

    public function testProhibitedStrategyThrowsException(): void
    {
        $prohibitedValue = TestProhibitedValue::fromNative('secret');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Attempted to access a prohibited value');

        $prohibitedValue->getSafeLoggableRepresentation();
    }

    public function testMaskingStrategy(): void
    {
        $maskValue = TestMaskValue::fromNative('1234567890123456');

        $masked = $maskValue->getSafeLoggableRepresentation();
        $this->assertSame('************3456', $masked);
    }

    public function testCustomMaskingInTrait(): void
    {
        $testValue = TestSecureValue::fromNative('1234567890');

        // Test custom masking (show first 2 and last 2)
        $customMasked = $testValue->maskCustom(2, 2, '*');
        $this->assertSame('12******90', $customMasked);
    }

    public function testSecurityPolicyNotDefinedThrowsException(): void
    {
        $testValue = TestIncompleteValue::fromNative('data');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No security strategy defined for context "persistence"');

        $testValue->getPersistentRepresentation();
    }

    public function testClassWithoutSecurityPolicyProviderThrowsException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must implement SecurityPolicyProvider');

        $invalidValue = TestInvalidValue::fromNative('data');
        $invalidValue->applySecurityPolicy(SecurityContext::LOGGING);
    }

    public function testEnumFeatures(): void
    {
        // Test enum cases
        $contexts = SecurityContext::cases();
        $this->assertCount(4, $contexts);
        
        $strategies = SecurityStrategy::cases();
        $this->assertCount(6, $strategies);

        // Test enum methods
        $hashStrategy = SecurityStrategy::HASH_SHA256;
        $this->assertSame('SHA256 Hashing', $hashStrategy->getLabel());
        $this->assertSame(8, $hashStrategy->getSecurityLevel());
        $this->assertFalse($hashStrategy->isReversible());
        $this->assertTrue($hashStrategy->isObfuscated());

        // Test context descriptions
        $loggingContext = SecurityContext::LOGGING;
        $this->assertSame('Logging', $loggingContext->getLabel());
        $this->assertStringContainsString('application logs', $loggingContext->getDescription());
    }

    public function testPolicyAnalysisMethods(): void
    {
        $policy = SecurityPolicy::create()
            ->logging(SecurityStrategy::HASH_SHA256)
            ->persistence(SecurityStrategy::ENCRYPT)
            ->reporting(SecurityStrategy::PLAINTEXT)
            ->serialization(SecurityStrategy::TOKENIZE)
            ->build();

        $this->assertSame(9, $policy->getMaxSecurityLevel()); // ENCRYPT has highest level
        $this->assertTrue($policy->hasReversibleStrategy()); // ENCRYPT and PLAINTEXT are reversible

        $summary = $policy->getSummary();
        $this->assertIsArray($summary);
        $this->assertCount(4, $summary);
    }
}

/**
 * Test class implementing the new generic security system.
 */
final class TestSecureValue implements Typed, SecurityPolicyProvider
{
    use TypedValue;
    use GenericSecurityTrait;

    public static function validate($value): ValidationResult
    {
        return new ValidationResult(); // Always valid for testing
    }

    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create()
            ->logging(SecurityStrategy::HASH_SHA256)
            ->persistence(SecurityStrategy::ENCRYPT)
            ->reporting(SecurityStrategy::PLAINTEXT)
            ->serialization(SecurityStrategy::TOKENIZE)
            ->build();
    }
}

/**
 * Test class with prohibited strategy.
 */
final class TestProhibitedValue implements Typed, SecurityPolicyProvider
{
    use TypedValue;
    use GenericSecurityTrait;

    public static function validate($value): ValidationResult
    {
        return new ValidationResult(); // Always valid for testing
    }

    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::prohibited();
    }
}

/**
 * Test class with masking strategy.
 */
final class TestMaskValue implements Typed, SecurityPolicyProvider
{
    use TypedValue;
    use GenericSecurityTrait;

    public static function validate($value): ValidationResult
    {
        return new ValidationResult(); // Always valid for testing
    }

    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create()
            ->logging(SecurityStrategy::MASK_PARTIAL)
            ->build();
    }
}

/**
 * Test class with incomplete policy.
 */
final class TestIncompleteValue implements Typed, SecurityPolicyProvider
{
    use TypedValue;
    use GenericSecurityTrait;

    public static function validate($value): ValidationResult
    {
        return new ValidationResult(); // Always valid for testing
    }

    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create()
            ->logging(SecurityStrategy::HASH_SHA256)
            ->build();
        // Missing persistence, reporting, serialization
    }
}

/**
 * Test class that doesn't implement SecurityPolicyProvider.
 */
final class TestInvalidValue implements Typed
{
    use TypedValue;
    use GenericSecurityTrait;

    public static function validate($value): ValidationResult
    {
        return new ValidationResult(); // Always valid for testing
    }
} 