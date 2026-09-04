<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\ValidationContext;

class ArraySchema extends Schema
{
    protected ?Schema $itemSchema = null;
    protected ?int $min = null;
    protected ?int $max = null;

    public function __construct(?Schema $itemSchema = null)
    {
        $this->itemSchema = $itemSchema;
    }

    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        if (!is_array($value)) {
            $context->addErrorByKey('array');
            return $value;
        }

        $count = count($value);

        if ($this->min !== null && $count < $this->min) {
            $context->addErrorByKey('array_min', ['min' => $this->min]);
        }

        if ($this->max !== null && $count > $this->max) {
            $context->addErrorByKey('array_max', ['max' => $this->max]);
        }

        if ($this->itemSchema === null) {
            return $value;
        }

        $cleanItems = [];
        foreach ($value as $key => $item) {
            $childContext = $context->createChild((string) $key);
            $cleanItems[$key] = $this->itemSchema->execute($item, $childContext);
        }

        return $cleanItems;
    }

    public function min(int $min): static
    {
        $clone = clone $this;
        $clone->min = $min;
        return $clone;
    }

    public function max(int $max): static
    {
        $clone = clone $this;
        $clone->max = $max;
        return $clone;
    }

    public function nonEmpty(): static
    {
        return $this->min(1);
    }
}
