<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\FullNameRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class FullName implements ValidationAttributeInterface
{
    public function __construct(
        public int $minWords = 2,
        public int $minWordLength = 2,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new FullNameRule($this->minWords, $this->minWordLength, $this->message);
        return $rule->validate($value, $context);
    }
}
