<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Tests\Examples;

use dzentota\TypedValue\Examples\DateOfBirth;
use dzentota\TypedValue\Security\ReportableData;
use dzentota\TypedValue\Security\SensitiveData;
use dzentota\TypedValue\Security\PersistentData;
use dzentota\TypedValue\Security\LoggingPolicy;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class DateOfBirthTest extends TestCase
{
    public function testDateOfBirthImplementsInterfaces(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        $this->assertInstanceOf(SensitiveData::class, $dob);
        $this->assertInstanceOf(ReportableData::class, $dob);
        $this->assertInstanceOf(PersistentData::class, $dob);
    }

    public function testDateOfBirthValidation(): void
    {
        $validDates = [
            '1990-01-01',
            '01/01/1990',
            'm/d/Y' => '01/01/1990',
            '1990-01-01 12:00:00'
        ];
        
        foreach ($validDates as $date) {
            $result = DateOfBirth::tryParse($date, $dob, $errors);
            
            $this->assertTrue($result, "Failed to parse valid date: $date");
            $this->assertInstanceOf(DateOfBirth::class, $dob);
            $this->assertEmpty($errors);
        }
    }

    public function testDateOfBirthValidationFailure(): void
    {
        $futureDate = (new DateTimeImmutable())->modify('+1 year')->format('Y-m-d');
        $invalidDates = [
            $futureDate, // Future date
            '1800-01-01', // Too old (over 150 years)
            'invalid-date',
            '13/13/1990', // Invalid month
            '32/01/1990', // Invalid day
            '',
            null,
            123
        ];
        
        foreach ($invalidDates as $date) {
            $result = DateOfBirth::tryParse($date, $dob, $errors);
            
            $this->assertFalse($result, "Should have failed to parse invalid date: " . var_export($date, true));
            $this->assertNull($dob);
            $this->assertNotEmpty($errors);
        }
    }

    public function testDateOfBirthFromDateTimeImmutable(): void
    {
        $dateTime = new DateTimeImmutable('1990-01-01');
        $dob = DateOfBirth::fromNative($dateTime);
        
        $this->assertInstanceOf(DateOfBirth::class, $dob);
        $this->assertSame('1990-01-01', $dob->toNative());
    }

    public function testDateOfBirthToNative(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        $this->assertSame('1990-01-01', $dob->toNative());
    }

    public function testDateOfBirthAge(): void
    {
        // Test with a known date
        $currentYear = (int)date('Y');
        $birthYear = $currentYear - 30;
        $dob = DateOfBirth::fromNative($birthYear . '-06-15');
        
        $age = $dob->getAge();
        
        $this->assertSame(30, $age);
    }

    public function testDateOfBirthAgeGroups(): void
    {
        $testCases = [
            [16, 'Under 18'],
            [20, '18-24'],
            [30, '25-34'],
            [40, '35-44'],
            [50, '45-54'],
            [60, '55-64'],
            [70, '65+']
        ];
        
        foreach ($testCases as [$age, $expectedGroup]) {
            $currentYear = (int)date('Y');
            $birthYear = $currentYear - $age;
            $dob = DateOfBirth::fromNative($birthYear . '-06-15');
            
            $ageGroup = $dob->getAgeGroup();
            
            $this->assertSame($expectedGroup, $ageGroup);
        }
    }

    public function testDateOfBirthLoggingPolicy(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        $policy = $dob->getLoggingPolicy();
        
        $this->assertInstanceOf(LoggingPolicy::class, $policy);
        $this->assertTrue($policy->isHashSha256());
    }

    public function testDateOfBirthSafeLoggableRepresentation(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        $safeRep = $dob->getSafeLoggableRepresentation();
        
        $this->assertIsString($safeRep);
        $this->assertNotSame('1990-01-01', $safeRep);
        $this->assertStringContainsString('SHA256:', $safeRep);
    }

    public function testDateOfBirthAnonymizedReportValue(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        $reportValue = $dob->getAnonymizedReportValue();
        
        $this->assertIsInt($reportValue);
        $this->assertGreaterThan(0, $reportValue);
        $this->assertLessThan(150, $reportValue);
        $this->assertSame($dob->getAge(), $reportValue);
    }

    public function testDateOfBirthPersistentRepresentation(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        $persistentRep = $dob->getPersistentRepresentation();
        
        $this->assertIsString($persistentRep);
        $this->assertNotSame('1990-01-01', $persistentRep);
        $this->assertSame($dob->getSafeLoggableRepresentation(), $persistentRep);
    }

    public function testDateOfBirthConsistency(): void
    {
        $dob1 = DateOfBirth::fromNative('1990-01-01');
        $dob2 = DateOfBirth::fromNative('1990-01-01');
        
        $this->assertSame($dob1->getAge(), $dob2->getAge());
        $this->assertSame($dob1->getAgeGroup(), $dob2->getAgeGroup());
        $this->assertSame($dob1->getAnonymizedReportValue(), $dob2->getAnonymizedReportValue());
        $this->assertSame($dob1->getPersistentRepresentation(), $dob2->getPersistentRepresentation());
    }

    public function testDateOfBirthUniqueness(): void
    {
        $dob1 = DateOfBirth::fromNative('1990-01-01');
        $dob2 = DateOfBirth::fromNative('1985-01-01');
        
        $this->assertNotSame($dob1->getAge(), $dob2->getAge());
        $this->assertNotSame($dob1->getAnonymizedReportValue(), $dob2->getAnonymizedReportValue());
        $this->assertNotSame($dob1->getPersistentRepresentation(), $dob2->getPersistentRepresentation());
    }

    public function testDateOfBirthBoundaryValidation(): void
    {
        $currentYear = (int)date('Y');
        
        // Test minimum age (1 day old)
        $yesterday = (new DateTimeImmutable())->modify('-1 day');
        $result = DateOfBirth::tryParse($yesterday->format('Y-m-d'), $dob, $errors);
        $this->assertTrue($result);
        
        // Test maximum age (150 years)
        $maxAge = (new DateTimeImmutable())->modify('-150 years');
        $result = DateOfBirth::tryParse($maxAge->format('Y-m-d'), $dob, $errors);
        $this->assertTrue($result);
        
        // Test over maximum age (151 years)
        $overMaxAge = (new DateTimeImmutable())->modify('-151 years');
        $result = DateOfBirth::tryParse($overMaxAge->format('Y-m-d'), $dob, $errors);
        $this->assertFalse($result);
        
        // Test future date
        $tomorrow = (new DateTimeImmutable())->modify('+1 day');
        $result = DateOfBirth::tryParse($tomorrow->format('Y-m-d'), $dob, $errors);
        $this->assertFalse($result);
    }

    public function testDateOfBirthFormatSupport(): void
    {
        $formats = [
            'Y-m-d' => '1990-01-01',
            'd/m/Y' => '01/01/1990',
            'm/d/Y' => '01/01/1990',
            'Y-m-d H:i:s' => '1990-01-01 12:00:00'
        ];
        
        foreach ($formats as $format => $dateString) {
            $result = DateOfBirth::tryParse($dateString, $dob, $errors);
            
            $this->assertTrue($result, "Failed to parse format $format: $dateString");
            $this->assertInstanceOf(DateOfBirth::class, $dob);
            $this->assertEmpty($errors);
        }
    }

    public function testDateOfBirthSecurityFeatures(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        // Test that all security representations are different from original
        $original = $dob->toNative();
        $logging = $dob->getSafeLoggableRepresentation();
        $persistent = $dob->getPersistentRepresentation();
        $report = $dob->getAnonymizedReportValue();
        
        $this->assertNotSame($original, $logging);
        $this->assertNotSame($original, $persistent);
        $this->assertNotSame($original, $report);
        
        // Test that hash representations are consistent
        $this->assertSame($logging, $persistent);
        
        // Test that report value is anonymized but useful
        $this->assertIsInt($report);
        $this->assertGreaterThan(0, $report);
    }
} 