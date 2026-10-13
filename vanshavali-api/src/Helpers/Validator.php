<?php

declare(strict_types=1);

namespace Vanshavali\Helpers;

class Validator
{
    private array $errors = [];

    /**
     * Validate required fields exist and are non-empty.
     */
    public function required(array $data, array $fields): self
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $this->errors[$field] = "{$field} is required";
            }
        }
        return $this;
    }

    /**
     * Validate an email field.
     */
    public function email(array $data, string $field): self
    {
        if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "Invalid email format";
        }
        return $this;
    }

    /**
     * Validate minimum string length.
     */
    public function minLength(array $data, string $field, int $min): self
    {
        if (isset($data[$field]) && mb_strlen($data[$field]) < $min) {
            $this->errors[$field] = "{$field} must be at least {$min} characters";
        }
        return $this;
    }

    /**
     * Validate maximum string length.
     */
    public function maxLength(array $data, string $field, int $max): self
    {
        if (isset($data[$field]) && mb_strlen($data[$field]) > $max) {
            $this->errors[$field] = "{$field} must be at most {$max} characters";
        }
        return $this;
    }

    /**
     * Validate a field matches a set of allowed values.
     */
    public function inList(array $data, string $field, array $allowed): self
    {
        if (isset($data[$field]) && !in_array($data[$field], $allowed, true)) {
            $this->errors[$field] = "{$field} must be one of: " . implode(', ', $allowed);
        }
        return $this;
    }

    /**
     * Check if validation passed.
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Reset the validator state.
     */
    public function reset(): self
    {
        $this->errors = [];
        return $this;
    }
}
