<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\Coercer;
use Arbor\Validator\Core\ValidationContext;

class IntSchema extends NumberSchema
{
    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        if ($this->shouldCoerce && (is_string($value) || is_float($value))) {
            $value = Coercer::toInt($value);
        }

        if (!is_int($value)) {
            $context->addErrorByKey('integer');
            return $value;
        }

        return parent::validateValue($value, $context);
    }
}
