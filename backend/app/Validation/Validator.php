<?php

declare(strict_types=1);

namespace App\Validation;

class Validator
{
    private array $errors = [];

    public function required(array $data, string $field, string $label): self
    {
        $value = $data[$field] ?? null;

        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->addError($field, $label . ' is required.');
        }

        return $this;
    }

    public function email(array $data, string $field, string $label): self
    {
        $value = $data[$field] ?? null;

        if (is_string($value) && trim($value) !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $label . ' must be a valid email address.');
        }

        return $this;
    }

    public function phone(array $data, string $field, string $label): self
    {
        $value = $data[$field] ?? null;

        if (is_string($value) && trim($value) !== '' && preg_match('/^\+?[0-9\s\-]{7,20}$/', $value) !== 1) {
            $this->addError($field, $label . ' must be a valid phone number.');
        }

        return $this;
    }

    public function password(array $data, string $field, string $label): self
    {
        $value = $data[$field] ?? null;

        if (!is_string($value) || trim($value) === '') {
            return $this;
        }

        if (strlen($value) < 8) {
            $this->addError($field, $label . ' must be at least 8 characters.');
        }

        if (preg_match('/[A-Z]/', $value) !== 1) {
            $this->addError($field, $label . ' must contain one uppercase letter.');
        }

        if (preg_match('/[a-z]/', $value) !== 1) {
            $this->addError($field, $label . ' must contain one lowercase letter.');
        }

        if (preg_match('/[0-9]/', $value) !== 1) {
            $this->addError($field, $label . ' must contain one number.');
        }

        if (preg_match('/[^A-Za-z0-9]/', $value) !== 1) {
            $this->addError($field, $label . ' must contain one special character.');
        }

        return $this;
    }

    public function matches(array $data, string $field, string $confirmationField, string $label): self
    {
        $value = $data[$field] ?? null;
        $confirmation = $data[$confirmationField] ?? null;

        if ($value !== null && $confirmation !== null && $value !== $confirmation) {
            $this->addError($confirmationField, $label . ' confirmation does not match.');
        }

        return $this;
    }

    public function maxLength(array $data, string $field, int $max, string $label): self
    {
        $value = $data[$field] ?? null;

        if (is_string($value) && strlen($value) > $max) {
            $this->addError($field, $label . ' must not exceed ' . $max . ' characters.');
        }

        return $this;
    }

    public function integer(array $data, string $field, string $label): self
    {
        $value = $data[$field] ?? null;

        if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, $label . ' must be a whole number.');
        }

        return $this;
    }

    public function positiveInteger(array $data, string $field, string $label): self
    {
        $value = $data[$field] ?? null;

        if ($value === null) {
            return $this;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        if ($parsed === false || $parsed <= 0) {
            $this->addError($field, $label . ' must be greater than zero.');
        }

        return $this;
    }

    public function date(array $data, string $field, string $label): self
    {
        $value = $data[$field] ?? null;

        if (is_string($value) && trim($value) !== '' && strtotime($value) === false) {
            $this->addError($field, $label . ' must be a valid date.');
        }

        return $this;
    }

    public function inList(array $data, string $field, array $allowed, string $label): self
    {
        $value = $data[$field] ?? null;

        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->addError($field, $label . ' must be one of: ' . implode(', ', $allowed) . '.');
        }

        return $this;
    }

    public function latitude(array $data, string $field, string $label): self
    {
        return $this->numberBetween($data, $field, -90, 90, $label);
    }

    public function longitude(array $data, string $field, string $label): self
    {
        return $this->numberBetween($data, $field, -180, 180, $label);
    }

    public function numberBetween(array $data, string $field, float $min, float $max, string $label): self
    {
        $value = $data[$field] ?? null;

        if ($value === null || $value === '') {
            return $this;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($parsed === false || $parsed < $min || $parsed > $max) {
            $this->addError($field, $label . ' must be a number between ' . $min . ' and ' . $max . '.');
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
