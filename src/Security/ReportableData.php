<?php

namespace dzentota\TypedValue\Security;

/**
 * Defines how an object should be represented in reports and analytics.
 * 
 * This interface ensures that business intelligence and reporting systems
 * receive anonymized or aggregated data without exposing personal information.
 */
interface ReportableData
{
    /**
     * Returns a value suitable for aggregation or analysis
     * that doesn't reveal individual identity.
     * 
     * @return string|int|float|bool|null
     */
    public function getAnonymizedReportValue(): mixed;
} 