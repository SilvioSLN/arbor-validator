<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\EmailRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Email implements ValidationAttributeInterface
{
    public function __construct(
        public bool $checkDns = false,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new EmailRule($this->checkDns, $this->message);
        return $rule->validate($value, $context);
    }
}
