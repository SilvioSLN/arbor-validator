<?php

declare(strict_types=1);

namespace Arbor\Validator\Exceptions;

use Arbor\Validator\Core\ValidationResult;

class ValidationException extends ValidatorException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        public readonly array $errors,
        public readonly ?ValidationResult $result = null,
        string $message = 'Os dados fornecidos são inválidos.',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        $firstError = $this->extractFirstError();
        if ($firstError !== null && $message === 'Os dados fornecidos são inválidos.') {
            $message = "Falha na validação: {$firstError}";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Retorna todos os erros agrupados por campo.
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna a primeira mensagem de erro global.
     */
    public function firstError(): ?string
    {
        return $this->extractFirstError();
    }

    /**
     * Retorna o primeiro erro de um campo específico.
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    private function extractFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return null;
    }
}
