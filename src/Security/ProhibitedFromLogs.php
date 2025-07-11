<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Marker interface for data that is categorically prohibited from being logged.
 * 
 * This empty marker interface provides a more explicit and powerful way
 * for loggers to identify data that should trigger an exception.
 * The intent becomes more obvious than simply checking for LoggingPolicy::PROHIBIT.
 */
interface ProhibitedFromLogs extends SensitiveData
{
} 