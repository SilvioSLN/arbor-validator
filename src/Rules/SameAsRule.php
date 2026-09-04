<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class SameAsRule implements RuleInterface
{
    public function __construct(
        public readonly string $otherField,
        public readonly ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $otherValue = $context->getRootValue($this->otherField);

        if ($value !== $otherValue) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('same_as', ['other' => $this->otherField]);
        }
    }
}
