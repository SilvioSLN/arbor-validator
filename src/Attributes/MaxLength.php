<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class MaxLength implements ValidationAttributeInterface
{
    public function __construct(
        public int $max,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (mb_strlen((string) $value) > $this->max) {
            if ($this->message !== null) {
                $context->addError($this->message);
            } else {
                $context->addErrorByKey('max_length', ['max' => $this->max]);
            }
            return false;
        }

        return true;
    }
}
