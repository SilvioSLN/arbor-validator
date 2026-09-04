<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\ErrorBag;
use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Core\ValidationResult;
use Arbor\Validator\Exceptions\ValidationException;

abstract class Schema
{
    protected bool $isOptional = false;
    protected bool $isNullable = false;
    protected bool $hasDefault = false;
    protected mixed $defaultValue = null;
    protected bool $hasCatch = false;
    protected mixed $catchValue = null;

    /**
     * @var list<callable(mixed): mixed>
     */
    protected array $transforms = [];

    /**
     * @var list<array{check: callable(mixed): bool, message: string, path: ?string}>
     */
    protected array $refinements = [];

    /**
     * @var list<callable(mixed, ValidationContext): void>
     */
    protected array $superRefinements = [];

    abstract public function validateValue(mixed $value, ValidationContext $context): mixed;

    public function optional(): static
    {
        $clone = clone $this;
        $clone->isOptional = true;
        return $clone;
    }

    public function nullable(): static
    {
        $clone = clone $this;
        $clone->isNullable = true;
        return $clone;
    }

    public function default(mixed $defaultValue): static
    {
        $clone = clone $this;
        $clone->hasDefault = true;
        $clone->defaultValue = $defaultValue;
        $clone->isOptional = true;
        return $clone;
    }

    public function catch(mixed $fallbackValue): static
    {
        $clone = clone $this;
        $clone->hasCatch = true;
        $clone->catchValue = $fallbackValue;
        return $clone;
    }

    public function transform(callable $fn): static
    {
        $clone = clone $this;
        $clone->transforms[] = $fn;
        return $clone;
    }

    public function refine(callable $check, string $message, ?string $path = null): static
    {
        $clone = clone $this;
        $clone->refinements[] = [
            'check' => $check,
            'message' => $message,
            'path' => $path,
        ];
        return $clone;
    }

    public function superRefine(callable $fn): static
    {
        $clone = clone $this;
        $clone->superRefinements[] = $fn;
        return $clone;
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function execute(mixed $value, ValidationContext $context): mixed
    {
        $startErrorsCount = count($context->errorBag);

        // Trata ausência ou valor nulo
        if ($value === null || $value === '') {
            if ($this->hasDefault) {
                return $this->defaultValue;
            }

            if ($this->isOptional || $this->isNullable) {
                return null;
            }

            $context->addErrorByKey('required');
            return $this->handleCatch($value, $context, $startErrorsCount);
        }

        // Executa validação de tipo específica da subclasse
        $result = $this->validateValue($value, $context);

        // Se houver erros gerados nessa etapa
        if (count($context->errorBag) > $startErrorsCount) {
            return $this->handleCatch($result, $context, $startErrorsCount);
        }

        // Executa transformações
        foreach ($this->transforms as $fn) {
            $result = $fn($result);
        }

        // Executa refinamentos (refine)
        foreach ($this->refinements as $refine) {
            $isValid = ($refine['check'])($result);
            if (!$isValid) {
                $context->addError($refine['message'], $refine['path']);
            }
        }

        // Executa super-refinamentos (superRefine)
        foreach ($this->superRefinements as $superFn) {
            $superFn($result, $context);
        }

        if (count($context->errorBag) > $startErrorsCount) {
            return $this->handleCatch($result, $context, $startErrorsCount);
        }

        return $result;
    }

    private function handleCatch(mixed $value, ValidationContext $context, int $startErrorsCount): mixed
    {
        if ($this->hasCatch) {
            // Em caso de catch, remove os erros adicionados por este schema
            // Para isso, reconstruímos os erros mantendo apenas os anteriores
            $all = $context->errorBag->all();
            $currPath = $context->path;
            if (isset($all[$currPath])) {
                unset($all[$currPath]);
            }
            // Substitui o errorBag por um limpo sem esse erro
            $newBag = new ErrorBag($all);
            // Copia para o bag de contexto
            $ref = new \ReflectionProperty($context->errorBag, 'errors');
            $ref->setValue($context->errorBag, $all);

            return $this->catchValue;
        }

        return $value;
    }

    public function safeParse(mixed $data, ?string $locale = null): ValidationResult
    {
        $context = new ValidationContext(path: '', rootData: $data, locale: $locale);
        $cleanData = $this->execute($data, $context);

        if ($context->errorBag->isNotEmpty()) {
            return ValidationResult::failure($context->errorBag, $cleanData);
        }

        return ValidationResult::success($cleanData);
    }

    /**
     * @throws ValidationException
     */
    public function parse(mixed $data, ?string $locale = null): mixed
    {
        $result = $this->safeParse($data, $locale);
        return $result->data();
    }
}
