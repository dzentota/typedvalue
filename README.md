# TypedValue

A PHP 7.4+ library for creating robust typed value objects with built-in validation, composite values, and enum support.

## Features

- 🎯 **Type-safe value objects** with automatic validation
- 🔒 **Immutable by design** - values cannot be changed after creation
- 🧩 **Composite values** - build complex objects from simpler typed values
- 📋 **Enum support** - create type-safe enumerations
- ✅ **Comprehensive validation** with detailed error reporting
- 🔄 **TryParse pattern** - safe parsing without exceptions
- 🕐 **Read-once values** - perfect for sensitive data like passwords
- 🧪 **100% test coverage** - reliable and battle-tested

## Installation

```bash
composer require dzentota/typedvalue
```

## Requirements

- PHP 7.4 or higher
- PHP 8.0+ recommended

## Quick Start

### Basic Typed Value

```php
<?php
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\TypedValue;
use dzentota\TypedValue\ValidationResult;

class Email implements Typed
{
    use TypedValue;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $result->addError('Invalid email format');
        }
        return $result;
    }
}

// Create from valid input
$email = Email::fromNative('user@example.com');
echo $email->toNative(); // user@example.com

// Safe parsing without exceptions
if (Email::tryParse('invalid-email', $parsedEmail, $validationResult)) {
    echo "Valid email: " . $parsedEmail->toNative();
} else {
    echo "Invalid: " . $validationResult->getFirstError()->getMessage();
}
```

### Enum Values

```php
<?php
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\Enum;

class Status implements Typed
{
    use Enum;
    
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
}

// Create enum instances
$status = Status::fromNative(Status::PENDING);
$status = Status::PENDING(); // Alternative syntax

// Type-safe comparison
if ($status->isSame(Status::APPROVED())) {
    echo "Status is approved";
}
```

### Composite Values

```php
<?php
use dzentota\TypedValue\Typed;
use dzentota\TypedValue\CompositeValue;

class UserProfile implements Typed
{
    use CompositeValue;
    
    private Email $email;
    private Name $name;
    private ?Age $age; // Optional field
}

class Name implements Typed
{
    use TypedValue;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!is_string($value) || strlen(trim($value)) < 2) {
            $result->addError('Name must be at least 2 characters');
        }
        return $result;
    }
}

class Age implements Typed
{
    use TypedValue;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if ($value !== null && (!is_int($value) || $value < 0 || $value > 150)) {
            $result->addError('Age must be between 0 and 150');
        }
        return $result;
    }
}

// Create composite value
$profile = UserProfile::fromNative([
    'email' => 'john@example.com',
    'name' => 'John Doe',
    'age' => 30
]);

// Access native array representation
$data = $profile->toNative();
// ['email' => 'john@example.com', 'name' => 'John Doe', 'age' => 30]
```

## Core Concepts

### The Typed Interface

All typed values implement the `Typed` interface, which provides:

```php
interface Typed
{
    // Safe parsing without exceptions
    public static function tryParse($value, ?Typed &$typed = null, ?ValidationResult &$result = null): bool;
    
    // Check if value is null
    public function isNull(): bool;
    
    // Compare with another typed value
    public function isSame(Typed $object): bool;
    
    // Create from native PHP value (throws on invalid input)
    public static function fromNative($native): Typed;
    
    // Convert back to native PHP value
    public function toNative();
}
```

### TypedValue Trait

The `TypedValue` trait provides the standard implementation:

```php
class Price implements Typed
{
    use TypedValue;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        
        if (!is_numeric($value)) {
            $result->addError('Price must be numeric');
        } elseif ($value < 0) {
            $result->addError('Price cannot be negative');
        }
        
        return $result;
    }
}
```

### Validation System

The validation system is built around three key classes:

#### ValidationResult

Container for validation results:

```php
$result = new ValidationResult();

// Add errors
$result->addError('Field is required');
$result->addError('Invalid format', 'email');

// Check status
if ($result->fails()) {
    foreach ($result->getErrors() as $error) {
        echo $error->getMessage();
        echo $error->getField(); // may be null
    }
}

// Clear all errors
$result->clear();
```

#### ValidationError

Individual error with optional field association:

```php
$error = new ValidationError('Invalid email format', 'email');
echo $error->getMessage(); // "Invalid email format"
echo $error->getField();   // "email"
```

#### ValidationException

Exception thrown when creating invalid typed values:

```php
try {
    $email = Email::fromNative('invalid-email');
} catch (ValidationException $e) {
    echo $e->getMessage(); // Error description
    $validationResult = $e->getValidationResult();
    // Access detailed validation errors
}
```

## Advanced Features

### Read-Once Values

Perfect for sensitive data that should only be accessed once:

```php
class SecretToken implements Typed
{
    use TypedValue;
    
    protected static bool $readOnce = true;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (empty($value)) {
            $result->addError('Token cannot be empty');
        }
        return $result;
    }
}

$token = SecretToken::fromNative('secret-key');
$value = $token->toNative(); // "secret-key"
$value = $token->toNative(); // throws DomainException
```

### Custom Composite Validation

Add business logic validation to composite values:

```php
class BankAccount implements Typed
{
    use CompositeValue;
    
    private AccountNumber $accountNumber;
    private RoutingNumber $routingNumber;
    
    public static function validateProperties(Typed $value): ValidationResult
    {
        $result = new ValidationResult();
        
        // Custom business logic
        $data = $value->toNative();
        if (!$this->isValidAccountCombination($data['accountNumber'], $data['routingNumber'])) {
            $result->addError('Invalid account and routing number combination');
        }
        
        return $result;
    }
    
    private static function isValidAccountCombination($account, $routing): bool
    {
        // Your business logic here
        return true;
    }
}
```

### Handling Unknown Fields

Control how composite values handle extra fields:

```php
class StrictComposite implements Typed
{
    use CompositeValue;
    
    private static bool $ignoreUnknownFields = false; // Strict mode
    
    private Email $email;
}

// This will throw an exception due to unknown 'extra' field
$composite = StrictComposite::fromNative([
    'email' => 'test@example.com',
    'extra' => 'unknown field'  // This causes an error
]);
```

## Complete Examples

### E-commerce Product

```php
class Product implements Typed
{
    use CompositeValue;
    
    private ProductId $id;
    private ProductName $name;
    private Price $price;
    private Category $category;
    private ?ProductDescription $description;
}

class ProductId implements Typed
{
    use TypedValue;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!is_string($value) || !preg_match('/^PRD-\d{6}$/', $value)) {
            $result->addError('Product ID must be in format PRD-123456');
        }
        return $result;
    }
}

class ProductName implements Typed
{
    use TypedValue;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!is_string($value) || strlen(trim($value)) < 3) {
            $result->addError('Product name must be at least 3 characters');
        }
        return $result;
    }
}

class Category implements Typed
{
    use Enum;
    
    const ELECTRONICS = 'electronics';
    const CLOTHING = 'clothing';
    const BOOKS = 'books';
    const HOME = 'home';
}

// Usage
$product = Product::fromNative([
    'id' => 'PRD-123456',
    'name' => 'Wireless Headphones',
    'price' => 99.99,
    'category' => Category::ELECTRONICS,
    'description' => 'High-quality wireless headphones'
]);

echo $product->toNative()['name']; // Wireless Headphones
```

### User Registration System

```php
class UserRegistration implements Typed
{
    use CompositeValue;
    
    private Email $email;
    private Password $password;
    private FullName $fullName;
    private ?PhoneNumber $phoneNumber;
    
    public static function validateProperties(Typed $value): ValidationResult
    {
        $result = new ValidationResult();
        $data = $value->toNative();
        
        // Business rule: phone required for international domains
        if (str_contains($data['email'], '.international') && empty($data['phoneNumber'])) {
            $result->addError('Phone number required for international email domains');
        }
        
        return $result;
    }
}

class Password implements Typed
{
    use TypedValue;
    
    protected static bool $readOnce = true; // Security: read once only
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        
        if (!is_string($value)) {
            $result->addError('Password must be a string');
            return $result;
        }
        
        if (strlen($value) < 8) {
            $result->addError('Password must be at least 8 characters');
        }
        
        if (!preg_match('/[A-Z]/', $value)) {
            $result->addError('Password must contain at least one uppercase letter');
        }
        
        if (!preg_match('/[a-z]/', $value)) {
            $result->addError('Password must contain at least one lowercase letter');
        }
        
        if (!preg_match('/[0-9]/', $value)) {
            $result->addError('Password must contain at least one number');
        }
        
        return $result;
    }
}

// Safe registration processing
if (UserRegistration::tryParse($_POST, $registration, $validationResult)) {
    // Process valid registration
    $userData = $registration->toNative();
    $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
    // ... save to database
} else {
    // Handle validation errors
    foreach ($validationResult->getErrors() as $error) {
        echo "Error in {$error->getField()}: {$error->getMessage()}\n";
    }
}
```

## Best Practices

### 1. Keep Validation Logic Simple

Each typed value should validate one concept:

```php
// Good: Single responsibility
class Email implements Typed
{
    use TypedValue;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $result->addError('Invalid email format');
        }
        return $result;
    }
}

// Avoid: Multiple responsibilities
class UserData implements Typed
{
    use TypedValue;
    
    public static function validate($value): ValidationResult
    {
        // Don't validate multiple concepts in one class
        // Use CompositeValue instead
    }
}
```

### 2. Use TryParse for User Input

Always use `tryParse()` when dealing with user input:

```php
// Good: Safe parsing
if (Email::tryParse($userInput, $email, $validationResult)) {
    // Process valid email
} else {
    // Handle validation errors gracefully
}

// Avoid: Direct fromNative with user input
try {
    $email = Email::fromNative($userInput); // May throw
} catch (ValidationException $e) {
    // Exception handling is less elegant
}
```

### 3. Leverage Read-Once for Security

Use read-once values for sensitive data:

```php
class ApiKey implements Typed
{
    use TypedValue;
    
    protected static bool $readOnce = true;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (empty($value) || strlen($value) < 32) {
            $result->addError('API key must be at least 32 characters');
        }
        return $result;
    }
}
```

### 4. Create Rich Domain Models

Use composition to build complex domain objects:

```php
class Order implements Typed
{
    use CompositeValue;
    
    private OrderId $id;
    private CustomerId $customerId;
    private OrderStatus $status;
    private Money $total;
    private OrderDate $createdAt;
}
```

## Testing

The library includes comprehensive PHPUnit tests. Run them with:

```bash
./vendor/bin/phpunit
```

Current test coverage: **100%** with 36 tests and 71 assertions.

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass: `./vendor/bin/phpunit`
2. Code follows PSR-12 standards
3. New features include comprehensive tests
4. Documentation is updated for new features

## Changelog

### Current Version
- ✅ Complete test coverage (36 tests)
- ✅ PHP 7.4+ and 8.x compatibility
- ✅ Comprehensive validation system
- ✅ Read-once value support
- ✅ Composite value objects
- ✅ Type-safe enums
- ✅ TryParse pattern implementation
