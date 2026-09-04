<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\EmojisRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Emojis implements ValidationAttributeInterface
{
    public function __construct(
        public bool $allow = true,
        public bool $only = false,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new EmojisRule($this->allow, $this->only, $this->message);
        return $rule->validate($value, $context);
    }
}
