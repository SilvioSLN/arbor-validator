<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\ValidationContext;

class ShapeSchema extends Schema
{
    /**
     * @var array<string, Schema>
     */
    protected array $fields;

    protected bool $strict = false;

    /**
     * @param array<string, Schema> $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        if (!is_array($value)) {
            $context->addErrorByKey('array');
            return $value;
        }

        $cleanData = [];

        // Valida campos definidos no schema
        foreach ($this->fields as $fieldName => $fieldSchema) {
            $childContext = $context->createChild($fieldName);
            $fieldVal = array_key_exists($fieldName, $value) ? $value[$fieldName] : null;

            $validatedVal = $fieldSchema->execute($fieldVal, $childContext);

            // Se o campo existia na entrada ou o schema produziu um valor não nulo / default
            if (array_key_exists($fieldName, $value) || $validatedVal !== null) {
                $cleanData[$fieldName] = $validatedVal;
            } elseif (!$fieldSchema->isOptional()) {
                $cleanData[$fieldName] = $validatedVal;
            }
        }

        // Se strict estiver ativo, verifica campos extras não permitidos
        if ($this->strict) {
            foreach ($value as $k => $v) {
                if (!array_key_exists($k, $this->fields)) {
                    $context->createChild((string) $k)->addError("Campo não reconhecido: {$k}");
                }
            }
        }

        return $cleanData;
    }

    /**
     * @return array<string, Schema>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function sameAs(string $targetField, string $compareField, ?string $message = null): static
    {
        return $this->refine(
            fn(array $data) => ($data[$targetField] ?? null) === ($data[$compareField] ?? null),
            message: $message ?? "O campo {$targetField} deve coincidir com {$compareField}",
            path: $targetField,
        );
    }

    /**
     * @param list<string> $keys
     */
    public function pick(array $keys): static
    {
        $selected = [];
        foreach ($keys as $key) {
            if (isset($this->fields[$key])) {
                $selected[$key] = $this->fields[$key];
            }
        }

        $clone = clone $this;
        $clone->fields = $selected;
        return $clone;
    }

    /**
     * @param list<string> $keys
     */
    public function omit(array $keys): static
    {
        $remaining = $this->fields;
        foreach ($keys as $key) {
            unset($remaining[$key]);
        }

        $clone = clone $this;
        $clone->fields = $remaining;
        return $clone;
    }

    /**
     * @param array<string, Schema> $fields
     */
    public function extend(array $fields): static
    {
        $clone = clone $this;
        $clone->fields = array_merge($this->fields, $fields);
        return $clone;
    }

    public function merge(ShapeSchema $other): static
    {
        return $this->extend($other->getFields());
    }

    public function partial(): static
    {
        $partialFields = [];
        foreach ($this->fields as $name => $schema) {
            $partialFields[$name] = $schema->optional();
        }

        $clone = clone $this;
        $clone->fields = $partialFields;
        return $clone;
    }

    public function strict(): static
    {
        $clone = clone $this;
        $clone->strict = true;
        return $clone;
    }

    public function strip(): static
    {
        $clone = clone $this;
        $clone->strict = false;
        return $clone;
    }
}
