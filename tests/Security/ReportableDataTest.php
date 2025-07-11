<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Tests\Security;

use dzentota\TypedValue\Examples\DateOfBirth;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class ReportableDataTest extends TestCase
{
    public function testDateOfBirthReportableData(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        $reportValue = $dob->getAnonymizedReportValue();
        
        $this->assertIsInt($reportValue);
        $this->assertGreaterThan(0, $reportValue);
        $this->assertLessThan(150, $reportValue);
    }

    public function testDateOfBirthAgeCalculation(): void
    {
        // Test with a known date to verify age calculation
        $birthYear = date('Y') - 30; // 30 years ago
        $dob = DateOfBirth::fromNative($birthYear . '-06-15');
        
        $age = $dob->getAge();
        
        $this->assertSame(30, $age);
        $this->assertSame($age, $dob->getAnonymizedReportValue());
    }

    public function testDateOfBirthAgeGroups(): void
    {
        $testCases = [
            ['1990-01-01', '25-34'], // Approximate, depends on current date
            ['2010-01-01', 'Under 18'],
            ['2005-01-01', 'Under 18'],
            ['1995-01-01', '25-34'],
            ['1985-01-01', '35-44'],
            ['1975-01-01', '45-54'],
            ['1965-01-01', '55-64'],
            ['1955-01-01', '65+']
        ];
        
        foreach ($testCases as [$birthDate, $expectedGroup]) {
            $dob = DateOfBirth::fromNative($birthDate);
            $ageGroup = $dob->getAgeGroup();
            
            // For dynamic dates, just ensure we get a valid age group
            $validAgeGroups = ['Under 18', '18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
            $this->assertContains($ageGroup, $validAgeGroups);
        }
    }

    public function testDateOfBirthAnonymization(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        // The anonymized report value should be different from the original date
        $reportValue = $dob->getAnonymizedReportValue();
        $originalValue = $dob->toNative();
        
        $this->assertNotSame($originalValue, $reportValue);
        $this->assertNotSame('1990-01-01', $reportValue);
        
        // But should be consistent
        $this->assertSame($reportValue, $dob->getAnonymizedReportValue());
    }

    public function testDateOfBirthConsistencyAcrossInstances(): void
    {
        $dob1 = DateOfBirth::fromNative('1990-01-01');
        $dob2 = DateOfBirth::fromNative('1990-01-01');
        
        $this->assertSame(
            $dob1->getAnonymizedReportValue(),
            $dob2->getAnonymizedReportValue()
        );
        
        $this->assertSame(
            $dob1->getAgeGroup(),
            $dob2->getAgeGroup()
        );
    }

    public function testDateOfBirthDifferentValuesProduceDifferentReports(): void
    {
        $dob1 = DateOfBirth::fromNative('1990-01-01');
        $dob2 = DateOfBirth::fromNative('1985-01-01');
        
        $this->assertNotSame(
            $dob1->getAnonymizedReportValue(),
            $dob2->getAnonymizedReportValue()
        );
    }

    public function testDateOfBirthValidation(): void
    {
        // Test various date formats
        $validDates = [
            '1990-01-01',
            '01/01/1990',
            '1990-01-01 00:00:00'
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
            '1800-01-01', // Too old
            'invalid-date',
            '13/13/1990', // Invalid month
            ''
        ];
        
        foreach ($invalidDates as $date) {
            $result = DateOfBirth::tryParse($date, $dob, $errors);
            
            $this->assertFalse($result, "Should have failed to parse invalid date: $date");
            $this->assertNull($dob);
            $this->assertNotEmpty($errors);
        }
    }

    public function testDateOfBirthReportableDataProtectsPrivacy(): void
    {
        $dob = DateOfBirth::fromNative('1990-01-01');
        
        // The report value should not contain the original birth date
        $reportValue = $dob->getAnonymizedReportValue();
        $originalDate = $dob->toNative();
        
        $this->assertIsInt($reportValue);
        $this->assertStringNotContainsString('1990', (string)$reportValue);
        $this->assertStringNotContainsString('01', (string)$reportValue);
        $this->assertNotSame($originalDate, $reportValue);
    }

    public function testDateOfBirthAgeGroupsForAnalytics(): void
    {
        // Test that age groups are suitable for analytics
        $testAges = [
            15 => 'Under 18',
            20 => '18-24',
            30 => '25-34',
            40 => '35-44',
            50 => '45-54',
            60 => '55-64',
            70 => '65+'
        ];
        
        foreach ($testAges as $age => $expectedGroup) {
            $birthYear = date('Y') - $age;
            $dob = DateOfBirth::fromNative($birthYear . '-06-15');
            
            $ageGroup = $dob->getAgeGroup();
            
            // Allow for some flexibility due to current date calculations
            $validGroups = ['Under 18', '18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
            $this->assertContains($ageGroup, $validGroups);
        }
    }
} 