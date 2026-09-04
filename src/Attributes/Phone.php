<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\PhoneRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Phone implements ValidationAttributeInterface
{
    public string $country;

    public function __construct(
        ?string $format = null,
        ?string $country = null,
        public bool $stripMask = false,
        public ?string $message = null,
    ) {
        $this->country = $format ?? $country ?? 'BR';
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new PhoneRule($this->country, $this->stripMask, $this->message);
        return $rule->validate($value, $context);
    }

    public function sanitize(mixed $value): mixed
    {
        $rule = new PhoneRule($this->country, $this->stripMask, $this->message);
        return $rule->sanitize($value);
    }
}
