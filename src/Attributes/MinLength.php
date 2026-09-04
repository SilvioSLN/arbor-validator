<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class MinLength implements ValidationAttributeInterface
{
    public function __construct(
        public int $min,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (mb_strlen((string) $value) < $this->min) {
            if ($this->message !== null) {
                $context->addError($this->message);
            } else {
                $context->addErrorByKey('min_length', ['min' => $this->min]);
            }
            return false;
        }

        return true;
    }
}
