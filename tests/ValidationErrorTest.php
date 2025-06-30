<?php

declare(strict_types=1);

namespace tests\dzentota\TypedValue;

use dzentota\TypedValue\ValidationError;
use PHPUnit\Framework\TestCase;

final class ValidationErrorTest extends TestCase
{
    public function test_validation_error_with_message_only()
    {
        $error = new ValidationError('Test error message');
        
        $this->assertEquals('Test error message', $error->getMessage());
        $this->assertNull($error->getField());
    }

    public function test_validation_error_with_message_and_field()
    {
        $error = new ValidationError('Test error message', 'fieldName');
        
        $this->assertEquals('Test error message', $error->getMessage());
        $this->assertEquals('fieldName', $error->getField());
    }

    public function test_validation_error_with_null_field()
    {
        $error = new ValidationError('Test error message', null);
        
        $this->assertEquals('Test error message', $error->getMessage());
        $this->assertNull($error->getField());
    }
} 