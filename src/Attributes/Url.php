<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\UrlRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Url implements ValidationAttributeInterface
{
    /**
     * @param list<string> $protocols
     */
    public function __construct(
        public array $protocols = ['http', 'https'],
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new UrlRule($this->protocols, $this->message);
        return $rule->validate($value, $context);
    }
}
