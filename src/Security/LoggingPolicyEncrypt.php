<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Trait for implementing encryption logging policy.
 * 
 * This trait provides a default implementation for values that should be
 * encrypted when logged (e.g., personal identifiers, addresses).
 * 
 * Note: This is a basic implementation using simple encryption.
 * In production, use proper encryption libraries and key management.
 */
trait LoggingPolicyEncrypt
{
    public static function getLoggingPolicy(): LoggingPolicy
    {
        return LoggingPolicy::encrypt();
    }

    public function getSafeLoggableRepresentation(): string
    {
        // Basic encryption using base64 - in production, use proper encryption
        $encrypted = base64_encode((string) $this->toNative());
        return 'ENC_' . $encrypted;
    }

    /**
     * Encrypt with a custom key (basic XOR encryption for demo purposes).
     * In production, use proper encryption algorithms like AES.
     * 
     * @param string $key Encryption key
     */
    protected function encryptWithKey(string $key): string
    {
        $value = (string) $this->toNative();
        $keyLength = strlen($key);
        $encrypted = '';
        
        for ($i = 0; $i < strlen($value); $i++) {
            $encrypted .= chr(ord($value[$i]) ^ ord($key[$i % $keyLength]));
        }
        
        return 'ENC_' . base64_encode($encrypted);
    }

    /**
     * Generate reversible encryption for audit purposes.
     * Warning: This is for demonstration only. Use proper encryption in production.
     * 
     * @param string $secretKey Secret key for encryption/decryption
     */
    protected function reversibleEncrypt(string $secretKey): string
    {
        $value = (string) $this->toNative();
        
        // Simple Caesar cipher with key-based shift (demo purposes only)
        $shift = array_sum(str_split($secretKey)) % 26;
        $encrypted = '';
        
        for ($i = 0; $i < strlen($value); $i++) {
            $char = $value[$i];
            if (ctype_alpha($char)) {
                $base = ctype_upper($char) ? ord('A') : ord('a');
                $encrypted .= chr((ord($char) - $base + $shift) % 26 + $base);
            } else {
                $encrypted .= $char;
            }
        }
        
        return 'REV_ENC_' . base64_encode($encrypted);
    }
} 