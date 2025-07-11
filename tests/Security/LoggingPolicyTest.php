<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue\Security;

use dzentota\TypedValue\Security\LoggingPolicy;
use PHPUnit\Framework\TestCase;

final class LoggingPolicyTest extends TestCase
{
    public function test_prohibit_policy()
    {
        $policy = LoggingPolicy::prohibit();
        
        $this->assertEquals(LoggingPolicy::PROHIBIT, $policy->getValue());
        $this->assertTrue($policy->isProhibit());
        $this->assertFalse($policy->isMaskPartial());
        $this->assertEquals('prohibit', (string) $policy);
    }

    public function test_mask_partial_policy()
    {
        $policy = LoggingPolicy::maskPartial();
        
        $this->assertEquals(LoggingPolicy::MASK_PARTIAL, $policy->getValue());
        $this->assertTrue($policy->isMaskPartial());
        $this->assertFalse($policy->isProhibit());
        $this->assertEquals('mask_partial', (string) $policy);
    }

    public function test_hash_sha256_policy()
    {
        $policy = LoggingPolicy::hashSha256();
        
        $this->assertEquals(LoggingPolicy::HASH_SHA256, $policy->getValue());
        $this->assertTrue($policy->isHashSha256());
        $this->assertFalse($policy->isTokenize());
        $this->assertEquals('hash_sha256', (string) $policy);
    }

    public function test_tokenize_policy()
    {
        $policy = LoggingPolicy::tokenize();
        
        $this->assertEquals(LoggingPolicy::TOKENIZE, $policy->getValue());
        $this->assertTrue($policy->isTokenize());
        $this->assertFalse($policy->isEncrypt());
        $this->assertEquals('tokenize', (string) $policy);
    }

    public function test_encrypt_policy()
    {
        $policy = LoggingPolicy::encrypt();
        
        $this->assertEquals(LoggingPolicy::ENCRYPT, $policy->getValue());
        $this->assertTrue($policy->isEncrypt());
        $this->assertFalse($policy->isPlaintext());
        $this->assertEquals('encrypt', (string) $policy);
    }

    public function test_plaintext_policy()
    {
        $policy = LoggingPolicy::plaintext();
        
        $this->assertEquals(LoggingPolicy::PLAINTEXT, $policy->getValue());
        $this->assertTrue($policy->isPlaintext());
        $this->assertFalse($policy->isProhibit());
        $this->assertEquals('plaintext', (string) $policy);
    }

    public function test_policy_equality()
    {
        $policy1 = LoggingPolicy::prohibit();
        $policy2 = LoggingPolicy::prohibit();
        $policy3 = LoggingPolicy::maskPartial();
        
        $this->assertTrue($policy1->equals($policy2));
        $this->assertFalse($policy1->equals($policy3));
    }

    public function test_all_policy_methods_return_false_for_different_types()
    {
        $policy = LoggingPolicy::prohibit();
        
        $this->assertTrue($policy->isProhibit());
        $this->assertFalse($policy->isMaskPartial());
        $this->assertFalse($policy->isHashSha256());
        $this->assertFalse($policy->isTokenize());
        $this->assertFalse($policy->isEncrypt());
        $this->assertFalse($policy->isPlaintext());
    }
} 