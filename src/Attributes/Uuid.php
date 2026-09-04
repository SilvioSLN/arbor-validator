<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\UuidRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Uuid implements ValidationAttributeInterface
{
    public function __construct(
        public int $version = 0,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new UuidRule($this->version, $this->message);
        return $rule->validate($value, $context);
    }
}
