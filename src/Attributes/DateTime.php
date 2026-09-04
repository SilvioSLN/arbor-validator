<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\DateRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class DateTime implements ValidationAttributeInterface
{
    public function __construct(
        public string $format = 'Y-m-d H:i:s',
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new DateRule($this->format, $this->message);
        return $rule->validate($value, $context);
    }
}
