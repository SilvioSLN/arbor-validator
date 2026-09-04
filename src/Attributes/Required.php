<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Required implements ValidationAttributeInterface
{
    public function __construct(public ?string $message = null)
    {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            if ($this->message !== null) {
                $context->addError($this->message);
            } else {
                $context->addErrorByKey('required');
            }
            return false;
        }

        return true;
    }
}
