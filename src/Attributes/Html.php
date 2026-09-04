<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\HtmlRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Html implements ValidationAttributeInterface
{
    public function __construct(
        public bool $sanitize = false,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new HtmlRule($this->sanitize, $this->message);
        return $rule->validate($value, $context);
    }

    public function sanitize(mixed $value): mixed
    {
        $rule = new HtmlRule($this->sanitize, $this->message);
        return $rule->sanitize($value);
    }
}
