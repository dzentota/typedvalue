<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue;

use dzentota\TypedValue\ValidationError;
use dzentota\TypedValue\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function test_new_validation_result_is_successful()
    {
        $result = new ValidationResult();
        
        $this->assertTrue($result->success());
        $this->assertFalse($result->fails());
        $this->assertEmpty($result->getErrors());
        $this->assertNull($result->getFirstError());
    }

    public function test_adding_error_makes_validation_fail()
    {
        $result = new ValidationResult();
        $result->addError('Test error message');
        
        $this->assertFalse($result->success());
        $this->assertTrue($result->fails());
        $this->assertCount(1, $result->getErrors());
    }

    public function test_adding_error_with_field()
    {
        $result = new ValidationResult();
        $result->addError('Test error message', 'fieldName');
        
        $errors = $result->getErrors();
        $this->assertCount(1, $errors);
        $this->assertInstanceOf(ValidationError::class, $errors[0]);
        $this->assertEquals('Test error message', $errors[0]->getMessage());
        $this->assertEquals('fieldName', $errors[0]->getField());
    }

    public function test_adding_multiple_errors()
    {
        $result = new ValidationResult();
        $result->addError('First error');
        $result->addError('Second error', 'field2');
        
        $this->assertFalse($result->success());
        $this->assertTrue($result->fails());
        $this->assertCount(2, $result->getErrors());
    }

    public function test_get_first_error()
    {
        $result = new ValidationResult();
        $result->addError('First error');
        $result->addError('Second error');
        
        $firstError = $result->getFirstError();
        $this->assertInstanceOf(ValidationError::class, $firstError);
        $this->assertEquals('First error', $firstError->getMessage());
    }

    public function test_clear_removes_all_errors()
    {
        $result = new ValidationResult();
        $result->addError('First error');
        $result->addError('Second error');
        
        $this->assertTrue($result->fails());
        
        $result->clear();
        
        $this->assertTrue($result->success());
        $this->assertFalse($result->fails());
        $this->assertEmpty($result->getErrors());
        $this->assertNull($result->getFirstError());
    }
} 