<?php

declare(strict_types=1);

namespace dzentota\TypedValue\Security;

/**
 * Security policy that maps contexts to strategies.
 * 
 * This class provides a fluent interface for configuring how sensitive data
 * should be handled in different contexts (logging, persistence, reporting, etc.).
 */
final readonly class SecurityPolicy
{
    private function __construct(
        private array $strategies = []
    ) {
    }

    /**
     * Create a new mutable security policy builder.
     */
    public static function create(): SecurityPolicyBuilder
    {
        return new SecurityPolicyBuilder();
    }

    /**
     * Set the strategy for logging context.
     */
    public function logging(SecurityStrategy $strategy): SecurityPolicyBuilder
    {
        return $this->toBuilder()->logging($strategy);
    }

    /**
     * Set the strategy for persistence context.
     */
    public function persistence(SecurityStrategy $strategy): SecurityPolicyBuilder
    {
        return $this->toBuilder()->persistence($strategy);
    }

    /**
     * Set the strategy for reporting context.
     */
    public function reporting(SecurityStrategy $strategy): SecurityPolicyBuilder
    {
        return $this->toBuilder()->reporting($strategy);
    }

    /**
     * Set the strategy for serialization context.
     */
    public function serialization(SecurityStrategy $strategy): SecurityPolicyBuilder
    {
        return $this->toBuilder()->serialization($strategy);
    }

    /**
     * Get the strategy for a given context.
     */
    public function getStrategy(SecurityContext $context): ?SecurityStrategy
    {
        return $this->strategies[$context->value] ?? null;
    }

    /**
     * Check if a strategy is defined for a given context.
     */
    public function hasStrategy(SecurityContext $context): bool
    {
        return isset($this->strategies[$context->value]);
    }

    /**
     * Get all defined strategies.
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Convert to a mutable builder.
     */
    public function toBuilder(): SecurityPolicyBuilder
    {
        return new SecurityPolicyBuilder($this->strategies);
    }

    /**
     * Create from builder.
     */
    public static function fromBuilder(SecurityPolicyBuilder $builder): self
    {
        return new self($builder->getStrategies());
    }

    /**
     * Create a policy with common secure defaults.
     */
    public static function secure(): self
    {
        return self::create()
            ->logging(SecurityStrategy::HASH_SHA256)
            ->persistence(SecurityStrategy::ENCRYPT)
            ->reporting(SecurityStrategy::PLAINTEXT)
            ->serialization(SecurityStrategy::TOKENIZE)
            ->build();
    }

    /**
     * Create a policy for highly sensitive data.
     */
    public static function prohibited(): self
    {
        return self::create()
            ->logging(SecurityStrategy::PROHIBIT)
            ->persistence(SecurityStrategy::ENCRYPT)
            ->reporting(SecurityStrategy::PROHIBIT)
            ->serialization(SecurityStrategy::PROHIBIT)
            ->build();
    }

    /**
     * Create a policy for PII data.
     */
    public static function pii(): self
    {
        return self::create()
            ->logging(SecurityStrategy::TOKENIZE)
            ->persistence(SecurityStrategy::ENCRYPT)
            ->reporting(SecurityStrategy::HASH_SHA256)
            ->serialization(SecurityStrategy::TOKENIZE)
            ->build();
    }

    /**
     * Create a policy for financial data.
     */
    public static function financial(): self
    {
        return self::create()
            ->logging(SecurityStrategy::MASK_PARTIAL)
            ->persistence(SecurityStrategy::ENCRYPT)
            ->reporting(SecurityStrategy::PLAINTEXT)
            ->serialization(SecurityStrategy::MASK_PARTIAL)
            ->build();
    }

    /**
     * Create a policy for public data.
     */
    public static function public(): self
    {
        return self::create()
            ->all(SecurityStrategy::PLAINTEXT)
            ->build();
    }

    /**
     * Get the maximum security level across all contexts.
     */
    public function getMaxSecurityLevel(): int
    {
        return max(array_map(
            fn(SecurityStrategy $strategy) => $strategy->getSecurityLevel(),
            $this->strategies
        ));
    }

    /**
     * Check if any strategy is reversible.
     */
    public function hasReversibleStrategy(): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->isReversible()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a summary of the policy configuration.
     */
    public function getSummary(): array
    {
        return array_map(
            fn(SecurityStrategy $strategy, string $context) => [
                'context' => $context,
                'strategy' => $strategy->value,
                'label' => $strategy->getLabel(),
                'security_level' => $strategy->getSecurityLevel(),
                'reversible' => $strategy->isReversible(),
            ],
            $this->strategies,
            array_keys($this->strategies)
        );
    }
}

/**
 * Mutable builder for SecurityPolicy.
 */
final class SecurityPolicyBuilder
{
    public function __construct(
        private array $strategies = []
    ) {
    }

    /**
     * Set the strategy for logging context.
     */
    public function logging(SecurityStrategy $strategy): self
    {
        $this->strategies[SecurityContext::LOGGING->value] = $strategy;
        return $this;
    }

    /**
     * Set the strategy for persistence context.
     */
    public function persistence(SecurityStrategy $strategy): self
    {
        $this->strategies[SecurityContext::PERSISTENCE->value] = $strategy;
        return $this;
    }

    /**
     * Set the strategy for reporting context.
     */
    public function reporting(SecurityStrategy $strategy): self
    {
        $this->strategies[SecurityContext::REPORTING->value] = $strategy;
        return $this;
    }

    /**
     * Set the strategy for serialization context.
     */
    public function serialization(SecurityStrategy $strategy): self
    {
        $this->strategies[SecurityContext::SERIALIZATION->value] = $strategy;
        return $this;
    }

    /**
     * Set the same strategy for all contexts.
     */
    public function all(SecurityStrategy $strategy): self
    {
        foreach (SecurityContext::cases() as $context) {
            $this->strategies[$context->value] = $strategy;
        }
        return $this;
    }

    /**
     * Get the current strategies.
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Build the immutable SecurityPolicy.
     */
    public function build(): SecurityPolicy
    {
        return SecurityPolicy::fromBuilder($this);
    }
} 