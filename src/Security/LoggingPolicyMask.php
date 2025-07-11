<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Trait for implementing partial masking logging policy.
 * 
 * This trait provides a default implementation for values that should be
 * partially masked when logged (e.g., credit card numbers, phone numbers).
 */
trait LoggingPolicyMask
{
    public static function getLoggingPolicy(): LoggingPolicy
    {
        return LoggingPolicy::maskPartial();
    }

    public function getSafeLoggableRepresentation(): string
    {
        $valueStr = (string) $this->toNative();
        
        if (strlen($valueStr) <= 4) {
            return str_repeat('*', strlen($valueStr));
        }
        
        // Show last 4 characters, mask the rest
        return str_repeat('*', strlen($valueStr) - 4) . substr($valueStr, -4);
    }

    /**
     * Custom masking with configurable visible characters.
     * 
     * @param int $visibleStart Number of characters to show at the start
     * @param int $visibleEnd Number of characters to show at the end
     * @param string $maskChar Character to use for masking
     */
    protected function maskCustom(int $visibleStart = 0, int $visibleEnd = 4, string $maskChar = '*'): string
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
} 