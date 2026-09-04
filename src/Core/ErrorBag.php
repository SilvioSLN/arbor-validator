<?php

declare(strict_types=1);

namespace Arbor\Validator\Core;

class ErrorBag implements \Countable
{
    /**
     * @var array<string, list<string>>
     */
    private array $errors = [];

    /**
     * @param array<string, list<string>> $initialErrors
     */
    public function __construct(array $initialErrors = [])
    {
        $this->errors = $initialErrors;
    }

    public function add(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->errors;
    }

    public function has(string $field): bool
    {
        return !empty($this->errors[$field]);
    }

    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public function isEmpty(): bool
    {
        return empty($this->errors);
    }

    public function isNotEmpty(): bool
    {
        return !empty($this->errors);
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->errors as $fieldErrors) {
            $count += count($fieldErrors);
        }

        return $count;
    }

    public function merge(ErrorBag $other, ?string $prefix = null): void
    {
        foreach ($other->all() as $field => $messages) {
            $key = $prefix !== null && $prefix !== '' ? "{$prefix}.{$field}" : $field;
            foreach ($messages as $msg) {
                $this->add($key, $msg);
            }
        }
    }
}
