# TypedValue

A PHP 8.1+ library for creating robust typed value objects with built-in validation, composite values, native enum support, and **comprehensive security features** for handling sensitive data.

## Features

- 🎯 **Type-safe value objects** with automatic validation
- 🔒 **Immutable by design** - values cannot be changed after creation
- 🧩 **Composite values** - build complex objects from simpler typed values
- 📋 **Enum support** - create type-safe enumerations
- ✅ **Comprehensive validation** with detailed error reporting
- 🔄 **TryParse pattern** - safe parsing without exceptions
- 🛡️ **Security-first design** with sensitive data protection
- 📊 **Logging policies** - control how sensitive data appears in logs
- 🕐 **Read-once values** - perfect for highly sensitive data like passwords and CVV codes
- 🎭 **Multiple obfuscation strategies** - masking, hashing, tokenization, encryption
- 🧪 **100% test coverage** - reliable and battle-tested

## Installation

```bash
composer require dzentota/typedvalue
```

## Requirements

- PHP 8.1 or higher

### Modern PHP Features

This library takes full advantage of modern PHP features:

- **Native Enums**: `SecurityStrategy` and `SecurityContext` use PHP 8.1+ backed enums
- **Match Expressions**: Advanced pattern matching for security policies
- **Readonly Properties**: Immutable security policy objects
- **Constructor Property Promotion**: Cleaner, more concise code
- **Union Types**: Flexible parameter handling

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

### Secure Sensitive Data Handling

```php
<?php
use dzentota\TypedValue\Examples\PrimaryAccountNumber;
use dzentota\TypedValue\Examples\UserPassword;
use dzentota\TypedValue\Examples\EmailAddress;
use dzentota\TypedValue\Examples\SessionId;
use dzentota\TypedValue\Examples\CVV;

// Credit Card with Masking
$pan = PrimaryAccountNumber::fromNative('4111111111111111');
echo $pan->getSafeLoggableRepresentation(); // "411111******1111"
echo $pan->getCardBrand(); // "Visa"

// Password with Prohibition & Read-Once
$password = UserPassword::fromNative('SecureP@ssw0rd123!');
$hash = $password->hash(); // Consumes the password
// $password->hash(); // Would throw LogicException - already consumed

// Email with Tokenization
$email = EmailAddress::fromNative('user@company.com');
echo $email->getSafeLoggableRepresentation(); // "EMAIL_a1b2c3d4e5f6g7h8"
echo $email->getDomain(); // "company.com" (safe to log)

// Session ID with Hashing
$sessionId = SessionId::generate();
echo $sessionId->getSafeLoggableRepresentation(); // SHA256 hash

// CVV with Prohibition & Read-Once
$cvv = CVV::fromNative('123');
$isValid = $cvv->verifyAndClear('123'); // true, and CVV is consumed
```

## Security Framework

### Security Strategies

The library provides six different security strategies for sensitive data:

```php
use dzentota\TypedValue\Security\SecurityStrategy;

SecurityStrategy::PROHIBIT       // Never log (throws exception)
SecurityStrategy::MASK_PARTIAL   // Show partial data: "****1234"
SecurityStrategy::HASH_SHA256    // SHA256 hash for correlation
SecurityStrategy::TOKENIZE       // Generate correlation tokens
SecurityStrategy::ENCRYPT        // Encrypted representation
SecurityStrategy::PLAINTEXT      // Safe to log as-is
```

### Modern Security System

Use the new unified security system with policies for different contexts:

```php
use dzentota\TypedValue\Security\{SecurityPolicy, SecurityStrategy, SecurityContext, GenericSecurityTrait};

// Credit Card Number
class CreditCardNumber implements Typed, SensitiveData, SecurityPolicyProvider
{
    use TypedValue, GenericSecurityTrait;
    
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::financial(); // Preset policy for financial data
    }
    
    // Automatically masks for logging: "4111111111111111" → "************1111"
    // Encrypts for persistence, masks for serialization
}

// API Key  
class ApiKey implements Typed, SensitiveData, SecurityPolicyProvider
{
    use TypedValue, GenericSecurityTrait;
    
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::secure(); // Hash for logging/persistence
    }
    
    // Automatically hashes with SHA256 for logging and persistence
}

// One-time Token
class OneTimeToken implements Typed, ProhibitedFromLogs, SecurityPolicyProvider
{
    use TypedValue, GenericSecurityTrait, ReadOnce {
        ReadOnce::toNative insteadof TypedValue;
    }
    
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::prohibited(); // Never log, prohibited everywhere
    }
    
    // Can only be read once, never logged
}
```

### Creating Custom Secure Types

```php
<?php
use dzentota\TypedValue\Security\{SensitiveData, SecurityPolicy, SecurityStrategy, SecurityContext};

class SocialSecurityNumber implements Typed, SensitiveData, SecurityPolicyProvider
{
    use TypedValue, GenericSecurityTrait;
    
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create()
            ->setStrategy(SecurityContext::LOGGING, SecurityStrategy::MASK_PARTIAL)
            ->setStrategy(SecurityContext::PERSISTENCE, SecurityStrategy::ENCRYPT)
            ->setStrategy(SecurityContext::REPORTING, SecurityStrategy::PLAINTEXT)
            ->setStrategy(SecurityContext::SERIALIZATION, SecurityStrategy::MASK_PARTIAL);
    }
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        
        if (!preg_match('/^\d{3}-?\d{2}-?\d{4}$/', $value)) {
            $result->addError('Invalid SSN format');
        }
        
        return $result;
    }
    
    // Custom masking: show only last 4 digits
    protected function maskPartial(): string
    {
        $ssn = preg_replace('/\D/', '', $this->toNative());
        return '***-**-' . substr($ssn, -4);
    }
}

$ssn = SocialSecurityNumber::fromNative('123-45-6789');
echo $ssn->getSafeLoggableRepresentation(); // "***-**-6789"
```

## Complete Security Example

### E-commerce Payment Processing

```php
<?php
use dzentota\TypedValue\Security\{SensitiveData, ProhibitedFromLogs, ReadOnce, SecurityPolicyProvider};

class PaymentRequest implements Typed
{
    use CompositeValue;
    
    private PrimaryAccountNumber $cardNumber;
    private CVV $cvv;
    private ExpiryDate $expiryDate;
    private Amount $amount;
    private CustomerEmail $customerEmail;
}

class Amount implements Typed, SensitiveData, SecurityPolicyProvider
{
    use TypedValue, GenericSecurityTrait;
    
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::public(); // Safe to log amounts
    }
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!is_numeric($value) || $value <= 0) {
            $result->addError('Amount must be positive');
        }
        return $result;
    }
}

class ExpiryDate implements Typed, SensitiveData, SecurityPolicyProvider
{
    use TypedValue, GenericSecurityTrait;
    
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create()
            ->setStrategy(SecurityContext::LOGGING, SecurityStrategy::MASK_PARTIAL)
            ->setStrategy(SecurityContext::PERSISTENCE, SecurityStrategy::ENCRYPT)
            ->setStrategy(SecurityContext::REPORTING, SecurityStrategy::PLAINTEXT)
            ->setStrategy(SecurityContext::SERIALIZATION, SecurityStrategy::MASK_PARTIAL);
    }
    
    protected function maskPartial(): string
    {
        // Show only the year: "12/25" → "**25"
        return '**' . substr($this->toNative(), -2);
    }
}

// Process payment securely
$paymentData = [
    'cardNumber' => '4111111111111111',
    'cvv' => '123',
    'expiryDate' => '12/25',
    'amount' => 99.99,
    'customerEmail' => 'customer@example.com'
];

if (PaymentRequest::tryParse($paymentData, $payment, $validationResult)) {
    // All sensitive data is automatically protected when logged
    $logger->info('Processing payment', [
        'card' => $payment->cardNumber->getSafeLoggableRepresentation(), // "411111******1111"
        'expiry' => $payment->expiryDate->getSafeLoggableRepresentation(), // "**25"
        'amount' => $payment->amount->getSafeLoggableRepresentation(), // 99.99
        'customer' => $payment->customerEmail->getSafeLoggableRepresentation(), // "TOKEN_abc123..."
        // CVV is ProhibitedFromLogs - attempting to log it would throw an exception
    ]);
    
    // Use CVV once for verification, then it's consumed
    $cvvValid = $payment->cvv->verifyAndClear($expectedCvv);
    
    // Process the payment...
} else {
    // Handle validation errors
    foreach ($validationResult->getErrors() as $error) {
        echo "Error: {$error->getMessage()}\n";
    }
}
```

### Advanced Security Configuration

```php
<?php
class CreditCardProcessor implements SecurityPolicyProvider
{
    use GenericSecurityTrait;
    
    public static function getSecurityPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create()
            ->setStrategy(SecurityContext::LOGGING, SecurityStrategy::MASK_PARTIAL)
            ->setStrategy(SecurityContext::PERSISTENCE, SecurityStrategy::ENCRYPT)
            ->setStrategy(SecurityContext::REPORTING, SecurityStrategy::TOKENIZE)
            ->setStrategy(SecurityContext::SERIALIZATION, SecurityStrategy::MASK_PARTIAL);
    }
    
    public function processPayment(PrimaryAccountNumber $pan, CVV $cvv): PaymentResult
    {
        // All security strategies are applied automatically
        $this->logger->info('Processing payment', [
            'pan' => $pan->getSafeLoggableRepresentation(), // Masked
            'pan_persistent' => $pan->getPersistentRepresentation(), // Encrypted
            'pan_report' => $pan->getAnonymizedReportValue(), // Tokenized
            // CVV is prohibited from logging
        ]);
        
        return new PaymentResult(/* ... */);
    }
}
```

## Enum Values

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

## Composite Values

```php
<?php
class UserProfile implements Typed
{
    use CompositeValue;
    
    private EmailAddress $email;    // Tokenized in logs
    private FullName $name;         // Plaintext (safe to log)
    private ?DateOfBirth $dob;      // Hashed in logs (optional field)
}

class FullName implements Typed, SensitiveData
{
    use TypedValue, LoggingPolicyPlaintext; // Names are generally safe to log
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!is_string($value) || strlen(trim($value)) < 2) {
            $result->addError('Name must be at least 2 characters');
        }
        return $result;
    }
}

class DateOfBirth implements Typed, SensitiveData
{
    use TypedValue, LoggingPolicyHash; // Hash DOB for privacy
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if ($value !== null && !strtotime($value)) {
            $result->addError('Invalid date format');
        }
        return $result;
    }
}

// Create composite value
$profile = UserProfile::fromNative([
    'email' => 'john@example.com',
    'name' => 'John Doe',
    'dob' => '1990-01-01'
]);

// Safe logging of all fields
$logger->info('User profile created', [
    'email' => $profile->email->getSafeLoggableRepresentation(), // "EMAIL_xyz789..."
    'name' => $profile->name->getSafeLoggableRepresentation(),   // "John Doe"
    'dob' => $profile->dob->getSafeLoggableRepresentation(),     // SHA256 hash
]);
```

## Advanced Security Features

### Custom Logging Policies

```php
class CustomSecureValue implements Typed, SensitiveData
{
    use TypedValue;
    
    public static function getLoggingSecurityStrategy(): SecurityStrategy
    {
        return SecurityStrategy::ENCRYPT; // Use encryption strategy
    }
    
    public function getSafeLoggableRepresentation(): string
    {
        // Custom encryption logic
        $encrypted = base64_encode($this->toNative());
        return "CUSTOM_ENC_{$encrypted}";
    }
}
```

### Read-Once with Business Logic

```php
class TwoFactorCode implements Typed, ProhibitedFromLogs
{
    use TypedValue, LoggingPolicyProhibit, ReadOnce {
        ReadOnce::toNative insteadof TypedValue;
    }
    
    private \DateTime $expiresAt;
    
    public static function validate($value): ValidationResult
    {
        $result = new ValidationResult();
        if (!preg_match('/^\d{6}$/', $value)) {
            $result->addError('2FA code must be 6 digits');
        }
        return $result;
    }
    
    public function verify(string $userInput): bool
    {
        if ($this->hasBeenConsumed()) {
            return false; // Code already used
        }
        
        if (new \DateTime() > $this->expiresAt) {
            return false; // Code expired
        }
        
        return $this->getValue() === $userInput; // Consumes the code
    }
    
    public static function fromNative($native): Typed
    {
        $code = parent::fromNative($native);
        $code->expiresAt = new \DateTime('+5 minutes'); // 5-minute expiry
        return $code;
    }
}
```

## Core Concepts

### The Typed Interface

All typed values implement the `Typed` interface:

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

### Security Interfaces

```php
interface SensitiveData
{
    public static function getLoggingSecurityStrategy(): SecurityStrategy;
    public function getSafeLoggableRepresentation();
}

interface ProhibitedFromLogs extends SensitiveData
{
    // Marker interface for data that must never be logged
}
```

## Best Practices

### 1. Choose Appropriate Logging Policies

```php
// Financial data - mask showing last 4 digits
class AccountNumber implements Typed, SensitiveData
{
    use TypedValue, LoggingPolicyMask;
}

// Session identifiers - hash for correlation
class SessionToken implements Typed, SensitiveData
{
    use TypedValue, LoggingPolicyHash;
}

// Personal identifiers - tokenize for privacy
class Username implements Typed, SensitiveData
{
    use TypedValue, LoggingPolicyTokenize;
}

// Secrets - never log
class PrivateKey implements Typed, ProhibitedFromLogs
{
    use TypedValue, LoggingPolicyProhibit, ReadOnce {
        ReadOnce::toNative insteadof TypedValue;
    }
}
```

### 2. Use Read-Once for Highly Sensitive Data

```php
// Use read-once for data that should only be accessed once
class EncryptionKey implements Typed, ProhibitedFromLogs
{
    use TypedValue, LoggingPolicyProhibit, ReadOnce {
        ReadOnce::toNative insteadof TypedValue;
    }
    
    public function encrypt(string $data): string
    {
        $key = $this->getValue(); // Consumes the key
        return openssl_encrypt($data, 'AES-256-CBC', $key);
    }
}
```

### 3. Implement Custom Security Logic

```php
class SecurePhoneNumber implements Typed, SensitiveData
{
    use TypedValue, LoggingPolicyMask;
    
    public function getSafeLoggableRepresentation(): string
    {
        $phone = $this->toNative();
        // Show country code and last 4 digits: "+1-555-***-1234"
        return preg_replace('/(\+\d{1,3}-)(\d{3}-)(\d{3}-)(\d{4})/', '$1$2***-$4', $phone);
    }
}
```

## Migration from Legacy Read-Once

If you were using the old `$readOnce` static property, migrate to the new `ReadOnce` trait:

```php
// Old way (deprecated)
class OldSecret extends StringValue
{
    protected static bool $readOnce = true;
}

// New way (recommended)
class NewSecret implements Typed, ProhibitedFromLogs
{
    use TypedValue, LoggingPolicyProhibit, ReadOnce {
        ReadOnce::toNative insteadof TypedValue;
    }
    
    public static function validate($value): ValidationResult
    {
        // Your validation logic
    }
}
```

## Testing

The library includes comprehensive PHPUnit tests. Run them with:

```bash
./vendor/bin/phpunit
```

Current test coverage: **100%** with comprehensive security feature testing.

## Security Considerations

1. **Never log sensitive data directly** - always use `getSafeLoggableRepresentation()`
2. **Use read-once for secrets** - passwords, tokens, keys should be consumed after use
3. **Choose appropriate masking** - show enough for debugging, hide enough for security
4. **Hash for correlation** - use consistent hashing for tracking without exposing data
5. **Validate early** - fail fast on invalid sensitive data
6. **Use composite values** - build complex secure objects from simple secure primitives

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass: `./vendor/bin/phpunit`
2. Code follows PSR-12 standards
3. New features include comprehensive tests
4. Security features include proper documentation
5. Sensitive data examples follow best practices

## Changelog

### Current Version
- ✅ **Complete security framework** with logging policies
- ✅ **Read-once trait** for highly sensitive data
- ✅ **Six logging policies**: prohibit, mask, hash, tokenize, encrypt, plaintext
- ✅ **Security traits** for rapid development
- ✅ **Comprehensive examples** for real-world scenarios
- ✅ **100% test coverage** including security features
- ✅ **PHP 8.1+ with modern enum support**
- ✅ **Backward-compatible** migration from legacy read-once
