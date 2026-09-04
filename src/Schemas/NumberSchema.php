<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\Coercer;
use Arbor\Validator\Core\ValidationContext;

class NumberSchema extends Schema
{
    protected ?float $min = null;
    protected ?string $minMessage = null;
    protected ?float $max = null;
    protected ?string $maxMessage = null;
    protected bool $positiveOnly = false;
    protected ?string $positiveMessage = null;
    protected bool $negativeOnly = false;
    protected ?string $negativeMessage = null;
    protected bool $shouldCoerce = false;

    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        if ($this->shouldCoerce && is_string($value)) {
            $value = Coercer::toFloat($value);
        }

        if (!is_int($value) && !is_float($value)) {
            $context->addErrorByKey('number');
            return $value;
        }

        if ($this->min !== null && $value < $this->min) {
            if ($this->minMessage !== null) {
                $context->addError($this->minMessage);
            } else {
                $context->addErrorByKey('min_value', ['min' => $this->min]);
            }
        }

        if ($this->max !== null && $value > $this->max) {
            if ($this->maxMessage !== null) {
                $context->addError($this->maxMessage);
            } else {
                $context->addErrorByKey('max_value', ['max' => $this->max]);
            }
        }

        if ($this->positiveOnly && $value <= 0) {
            if ($this->positiveMessage !== null) {
                $context->addError($this->positiveMessage);
            } else {
                $context->addErrorByKey('positive');
            }
        }

        if ($this->negativeOnly && $value >= 0) {
            if ($this->negativeMessage !== null) {
                $context->addError($this->negativeMessage);
            } else {
                $context->addErrorByKey('negative');
            }
        }

        return $value;
    }

    public function coerce(): static
    {
        $clone = clone $this;
        $clone->shouldCoerce = true;
        return $clone;
    }

    public function min(int|float $min, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->min = (float) $min;
        $clone->minMessage = $message;
        return $clone;
    }

    public function max(int|float $max, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->max = (float) $max;
        $clone->maxMessage = $message;
        return $clone;
    }

    public function positive(?string $message = null): static
    {
        $clone = clone $this;
        $clone->positiveOnly = true;
        $clone->positiveMessage = $message;
        return $clone;
    }

    public function negative(?string $message = null): static
    {
        $clone = clone $this;
        $clone->negativeOnly = true;
        $clone->negativeMessage = $message;
        return $clone;
    }

    public function int(): IntSchema
    {
        $schema = new IntSchema();
        if ($this->min !== null) {
            $schema = $schema->min((int) $this->min, $this->minMessage);
        }
        if ($this->max !== null) {
            $schema = $schema->max((int) $this->max, $this->maxMessage);
        }
        if ($this->positiveOnly) {
            $schema = $schema->positive($this->positiveMessage);
        }
        if ($this->negativeOnly) {
            $schema = $schema->negative($this->negativeMessage);
        }
        if ($this->shouldCoerce) {
            $schema = $schema->coerce();
        }
        if ($this->isOptional) {
            $schema = $schema->optional();
        }
        if ($this->isNullable) {
            $schema = $schema->nullable();
        }
        return $schema;
    }

    public function float(): FloatSchema
    {
        $schema = new FloatSchema();
        if ($this->min !== null) {
            $schema = $schema->min($this->min, $this->minMessage);
        }
        if ($this->max !== null) {
            $schema = $schema->max($this->max, $this->maxMessage);
        }
        if ($this->positiveOnly) {
            $schema = $schema->positive($this->positiveMessage);
        }
        if ($this->negativeOnly) {
            $schema = $schema->negative($this->negativeMessage);
        }
        if ($this->shouldCoerce) {
            $schema = $schema->coerce();
        }
        if ($this->isOptional) {
            $schema = $schema->optional();
        }
        if ($this->isNullable) {
            $schema = $schema->nullable();
        }
        return $schema;
    }
}
