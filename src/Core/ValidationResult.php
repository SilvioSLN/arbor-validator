<?php

declare(strict_types=1);

namespace Arbor\Validator\Core;

use Arbor\Validator\Exceptions\ValidationException;

/**
 * Encapsulates the outcome of a validation operation.
 *
 * Provides inspection methods for success/failure, clean data extraction,
 * and field-level or global error queries.
 *
 * @api
 */
class ValidationResult
{
    /**
     * Whether validation succeeded with zero errors.
     */
    public readonly bool $success;

    /**
     * @param bool $success
     * @param mixed $data
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        bool $success,
        private readonly mixed $data = null,
        private readonly array $errors = [],
    ) {
        $this->success = $success;
    }

    public static function success(mixed $data): self
    {
        return new self(true, $data, []);
    }

    /**
     * @param array<string, list<string>>|ErrorBag $errors
     */
    public static function failure(array|ErrorBag $errors, mixed $partialData = null): self
    {
        $errArray = $errors instanceof ErrorBag ? $errors->all() : $errors;
        return new self(false, $partialData, $errArray);
    }

    public function isValid(): bool
    {
        return $this->success;
    }

    public function failed(): bool
    {
        return !$this->success;
    }

    /**
     * Retorna os dados validados (DTO ou array).
     * Lança ValidationException se a validação tiver falhado.
     *
     * @throws ValidationException
     */
    public function data(): mixed
    {
        if ($this->failed()) {
            throw new ValidationException($this->errors, $this);
        }

        return $this->data;
    }

    /**
     * Retorna os dados sem lançar exceção, mesmo em caso de falha.
     */
    public function safeData(): mixed
    {
        return $this->data;
    }

    /**
     * Retorna todos os erros agrupados por campo: ['email' => ['E-mail inválido'], ...]
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna apenas a primeira mensagem de erro global.
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return null;
    }

    /**
     * Retorna o primeiro erro de um campo específico.
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Retorna todos os erros de um campo específico.
     *
     * @return list<string>
     */
    public function fieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Verifica se um campo específico possui erro.
     */
    public function hasError(string $field): bool
    {
        return !empty($this->errors[$field]);
    }
}
