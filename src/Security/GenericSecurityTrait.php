<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

use LogicException;

/**
 * Generic trait for applying security policies to typed values.
 * 
 * This trait provides a unified way to apply security strategies
 * across different contexts (logging, persistence, reporting, etc.).
 */
trait GenericSecurityTrait
{
    /**
     * Apply security policy for a given context.
     */
    public function applySecurityPolicy(SecurityContext $context): mixed
    {
        if (!$this instanceof SecurityPolicyProvider) {
            throw new LogicException(
                sprintf('Class %s must implement SecurityPolicyProvider to use GenericSecurityTrait', static::class)
            );
        }

        $policy = static::getSecurityPolicy();
        $strategy = $policy->getStrategy($context);

        if ($strategy === null) {
            throw new LogicException(
                sprintf('No security strategy defined for context "%s" in class %s', $context->value, static::class)
            );
        }

        return $this->applyStrategy($strategy);
    }

    /**
     * Apply a specific security strategy using modern match expression.
     */
    protected function applyStrategy(SecurityStrategy $strategy): mixed
    {
        return match($strategy) {
            SecurityStrategy::PROHIBIT => throw new LogicException(
                sprintf('Attempted to access a prohibited value of type: %s', static::class)
            ),
            SecurityStrategy::MASK_PARTIAL => $this->maskPartial(),
            SecurityStrategy::HASH_SHA256 => $this->hashSha256(),
            SecurityStrategy::TOKENIZE => $this->tokenize(),
            SecurityStrategy::ENCRYPT => $this->encrypt(),
            SecurityStrategy::PLAINTEXT => $this->toNative(),
        };
    }

    /**
     * Implementation-specific methods for different strategies.
     */
    protected function maskPartial(): string
    {
        $valueStr = (string) $this->toNative();
        
        if (strlen($valueStr) <= 4) {
            return str_repeat('*', strlen($valueStr));
        }
        
        // Show last 4 characters, mask the rest
        return str_repeat('*', strlen($valueStr) - 4) . substr($valueStr, -4);
    }

    protected function hashSha256(): string
    {
        return 'SHA256:' . hash('sha256', (string) $this->toNative());
    }

    protected function tokenize(): string
    {
        return 'TOKEN_' . substr(hash('sha256', (string) $this->toNative()), 0, 16);
    }

    protected function encrypt(): string
    {
        // Basic encryption using base64 - in production, use proper encryption
        $encrypted = base64_encode((string) $this->toNative());
        return 'ENC_' . $encrypted;
    }

    /**
     * Custom masking with configurable visible characters.
     */
    public function maskCustom(int $visibleStart = 0, int $visibleEnd = 4, string $maskChar = '*'): string
    {
        $valueStr = (string) $this->toNative();
        $length = strlen($valueStr);
        
        if ($length <= ($visibleStart + $visibleEnd)) {
            return str_repeat($maskChar, $length);
        }
        
        $start = $visibleStart > 0 ? substr($valueStr, 0, $visibleStart) : '';
        $end = $visibleEnd > 0 ? substr($valueStr, -$visibleEnd) : '';
        $maskedLength = $length - $visibleStart - $visibleEnd;
        
        return $start . str_repeat($maskChar, $maskedLength) . $end;
    }

    protected function hashWithSalt(string $salt): string
    {
        return 'SHA256:' . hash('sha256', $salt . (string) $this->toNative());
    }

    protected function tokenizeWithPrefix(string $prefix): string
    {
        return $prefix . '_' . substr(hash('sha256', (string) $this->toNative()), 0, 16);
    }

    /**
     * Advanced encryption with proper key derivation (for production use).
     */
    protected function encryptWithKey(string $key, string $algorithm = 'aes-256-gcm'): string
    {
        $data = (string) $this->toNative();
        $iv = random_bytes(16);
        $tag = '';
        
        $encrypted = openssl_encrypt($data, $algorithm, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($encrypted === false) {
            throw new LogicException('Encryption failed');
        }
        
        return 'SECURE_ENC_' . base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt data encrypted with encryptWithKey.
     */
    protected function decryptWithKey(string $encryptedData, string $key, string $algorithm = 'aes-256-gcm'): string
    {
        if (!str_starts_with($encryptedData, 'SECURE_ENC_')) {
            throw new LogicException('Invalid encrypted data format');
        }
        
        $data = base64_decode(substr($encryptedData, 11));
        $iv = substr($data, 0, 16);
        $tag = substr($data, 16, 16);
        $encrypted = substr($data, 32);
        
        $decrypted = openssl_decrypt($encrypted, $algorithm, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($decrypted === false) {
            throw new LogicException('Decryption failed');
        }
        
        return $decrypted;
    }

    /**
     * Backward compatibility methods.
     */
    public function getSafeLoggableRepresentation(): mixed
    {
        return $this->applySecurityPolicy(SecurityContext::LOGGING);
    }

    public function getPersistentRepresentation(): mixed
    {
        return $this->applySecurityPolicy(SecurityContext::PERSISTENCE);
    }

    public function getAnonymizedReportValue(): mixed
    {
        return $this->applySecurityPolicy(SecurityContext::REPORTING);
    }

    public function getSecureSerializationValue(): mixed
    {
        return $this->applySecurityPolicy(SecurityContext::SERIALIZATION);
    }

    /**
     * Get the security strategy for logging (SensitiveData interface implementation).
     */
    public static function getLoggingSecurityStrategy(): SecurityStrategy
    {
        if (!method_exists(static::class, 'getSecurityPolicy')) {
            throw new LogicException(
                sprintf('Class %s must implement SecurityPolicyProvider to use GenericSecurityTrait', static::class)
            );
        }

        $policy = static::getSecurityPolicy();
        $strategy = $policy->getStrategy(SecurityContext::LOGGING);

        if ($strategy === null) {
            throw new LogicException(
                sprintf('No logging strategy defined in security policy for class %s', static::class)
            );
        }

        return $strategy;
    }

    /**
     * Get security policy summary for debugging.
     */
    public function getSecuritySummary(): array
    {
        if (!$this instanceof SecurityPolicyProvider) {
            return ['error' => 'Class does not implement SecurityPolicyProvider'];
        }

        return static::getSecurityPolicy()->getSummary();
    }

    /**
     * Validate that the security policy covers all required contexts.
     */
    public function validateSecurityPolicy(): bool
    {
        if (!$this instanceof SecurityPolicyProvider) {
            return false;
        }

        $policy = static::getSecurityPolicy();
        
        foreach (SecurityContext::cases() as $context) {
            if (!$policy->hasStrategy($context)) {
                return false;
            }
        }
        
        return true;
    }
} 