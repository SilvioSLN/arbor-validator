<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\Coercer;
use Arbor\Validator\Core\ValidationContext;

class BoolSchema extends Schema
{
    protected bool $shouldCoerce = false;

    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        if ($this->shouldCoerce) {
            $value = Coercer::toBool($value);
        }

        if (!is_bool($value)) {
            $context->addErrorByKey('boolean');
            return $value;
        }

        return $value;
    }

    public function coerce(): static
    {
        $clone = clone $this;
        $clone->shouldCoerce = true;
        return $clone;
    }
}
