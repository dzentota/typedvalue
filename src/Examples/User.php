<?php

namespace dzentota\TypedValue\Examples;

use JsonSerializable;

/**
 * Example User entity demonstrating the security-aware types pattern.
 * 
 * This shows how sensitive data is automatically protected across all
 * interaction points (logging, storage, serialization, reporting).
 */
final class User implements JsonSerializable
{
    private int $id;
    private string $username;
    private EmailAddress $email;
    private UserPassword $password;
    private ?PrimaryAccountNumber $creditCard;
    private ?DateOfBirth $dateOfBirth;
    private SessionId $sessionId;

    public function __construct(
        int $id,
        string $username,
        EmailAddress $email,
        UserPassword $password,
        ?PrimaryAccountNumber $creditCard = null,
        ?DateOfBirth $dateOfBirth = null,
        ?SessionId $sessionId = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->creditCard = $creditCard;
        $this->dateOfBirth = $dateOfBirth;
        $this->sessionId = $sessionId ?? SessionId::generate();
    }

    /**
     * Safe JSON representation for API responses.
     * Sensitive data is automatically handled by each TypedValue's jsonSerialize method.
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email->jsonSerialize(), // Use EmailAddress::jsonSerialize()
            'has_payment_method' => $this->creditCard !== null,
            'payment_method' => $this->creditCard?->jsonSerialize(), // Use PAN::jsonSerialize() if set
            'age_group' => $this->dateOfBirth?->getAgeGroup(),
            'session_info' => [
                'id' => $this->sessionId->getDebugRepresentation(),
                'type' => 'authenticated'
            ]
        ];
    }

    /**
     * Get data for database persistence.
     * All sensitive fields automatically use their getPersistentRepresentation() methods.
     */
    public function getPersistentData(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email->getPersistentRepresentation(),
            'password_hash' => $this->password->getHashedRepresentation(),
            'credit_card' => $this->creditCard?->getPersistentRepresentation(),
            'date_of_birth' => $this->dateOfBirth?->getPersistentRepresentation(),
            'session_id' => $this->sessionId->getPersistentRepresentation()
        ];
    }

    /**
     * Get anonymized data for reporting and analytics.
     */
    public function getReportData(): array
    {
        return [
            'id' => $this->id,
            'username_length' => strlen($this->username),
            'email_domain' => $this->email->getDomain(),
            'is_corporate_email' => $this->email->isCorporateEmail(),
            'age' => $this->dateOfBirth?->getAnonymizedReportValue(),
            'age_group' => $this->dateOfBirth?->getAgeGroup(),
            'has_payment_method' => $this->creditCard !== null,
            'payment_brand' => $this->creditCard?->getCardBrand(),
            'password_strength' => $this->password->getStrengthScore(),
            'session_duration' => $this->sessionId->getSessionDuration()
        ];
    }

    /**
     * Update user's email address with validation.
     */
    public function updateEmail(EmailAddress $newEmail): void
    {
        $this->email = $newEmail;
    }

    /**
     * Update user's password with validation.
     */
    public function updatePassword(UserPassword $newPassword): void
    {
        $this->password = $newPassword;
    }

    /**
     * Add or update credit card information.
     */
    public function updateCreditCard(PrimaryAccountNumber $creditCard): void
    {
        $this->creditCard = $creditCard;
    }

    // Getters that return the actual typed values
    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): EmailAddress
    {
        return $this->email;
    }

    public function getPassword(): UserPassword
    {
        return $this->password;
    }

    public function getCreditCard(): ?PrimaryAccountNumber
    {
        return $this->creditCard;
    }

    public function getDateOfBirth(): ?DateOfBirth
    {
        return $this->dateOfBirth;
    }

    public function getSessionId(): SessionId
    {
        return $this->sessionId;
    }
} 