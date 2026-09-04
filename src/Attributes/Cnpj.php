<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\CnpjRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Cnpj implements ValidationAttributeInterface
{
    public function __construct(
        public bool $allowAlphanumeric = true,
        public bool $stripMask = false,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new CnpjRule($this->allowAlphanumeric, $this->stripMask, $this->message);
        return $rule->validate($value, $context);
    }

    public function sanitize(mixed $value): mixed
    {
        $rule = new CnpjRule($this->allowAlphanumeric, $this->stripMask, $this->message);
        return $rule->sanitize($value);
    }
}
