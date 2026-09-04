<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\Coercer;
use Arbor\Validator\Core\ValidationContext;

class FloatSchema extends NumberSchema
{
    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        if ($this->shouldCoerce && (is_string($value) || is_int($value))) {
            $value = Coercer::toFloat($value);
        }

        if (!is_float($value)) {
            $context->addErrorByKey('float');
            return $value;
        }

        return parent::validateValue($value, $context);
    }
}
