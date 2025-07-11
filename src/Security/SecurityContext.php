<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Security context enum for sensitive data handling.
 * 
 * Defines the different contexts where security policies can be applied.
 */
enum SecurityContext: string
{
    case LOGGING = 'logging';
    case PERSISTENCE = 'persistence';
    case REPORTING = 'reporting';
    case SERIALIZATION = 'serialization';

    public function isLogging(): bool
    {
        return $this === self::LOGGING;
    }

    public function isPersistence(): bool
    {
        return $this === self::PERSISTENCE;
    }

    public function isReporting(): bool
    {
        return $this === self::REPORTING;
    }

    public function isSerialization(): bool
    {
        return $this === self::SERIALIZATION;
    }

    public function getLabel(): string
    {
        return match($this) {
            self::LOGGING => 'Logging',
            self::PERSISTENCE => 'Data Persistence',
            self::REPORTING => 'Analytics & Reporting',
            self::SERIALIZATION => 'API Serialization',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::LOGGING => 'How data appears in application logs',
            self::PERSISTENCE => 'How data is stored in databases',
            self::REPORTING => 'How data appears in reports and analytics',
            self::SERIALIZATION => 'How data is exposed in API responses',
        };
    }
} 