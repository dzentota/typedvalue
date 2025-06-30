<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue;

use dzentota\TypedValue\ValidationException;
use dzentota\TypedValue\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function test_validation_exception_with_validation_result()
    {
        $validationResult = new ValidationResult();
        $validationResult->addError('Test error');
        
        $exception = new ValidationException('Validation failed', $validationResult);
        
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
        $this->assertSame($validationResult, $exception->getValidationResult());
    }

    public function test_validation_exception_with_custom_code()
    {
        $validationResult = new ValidationResult();
        $validationResult->addError('Test error');
        
        $exception = new ValidationException('Validation failed', $validationResult, 400);
        
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertSame($validationResult, $exception->getValidationResult());
    }

    public function test_validation_exception_with_previous_exception()
    {
        $validationResult = new ValidationResult();
        $validationResult->addError('Test error');
        
        $previousException = new \Exception('Previous exception');
        $exception = new ValidationException('Validation failed', $validationResult, 422, $previousException);
        
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
        $this->assertSame($validationResult, $exception->getValidationResult());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function test_validation_exception_extends_domain_exception()
    {
        $validationResult = new ValidationResult();
        $exception = new ValidationException('Test', $validationResult);
        
        $this->assertInstanceOf(\DomainException::class, $exception);
    }
} 