<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class DomainRule implements RuleInterface
{
    public function __construct(
        public readonly ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            $this->fail($context);
            return false;
        }

        if (!self::isValid($value)) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    public static function isValid(string $domain): bool
    {
        $domain = trim($domain);

        // Rejeita se tiver esquema, caminhos ou portas
        if (str_contains($domain, '://') || str_contains($domain, '/') || str_contains($domain, ':')) {
            return false;
        }

        return (bool) preg_match(
            '/^(?=.{1,253}$)((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}$/',
            $domain
        );
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('domain');
        }
    }
}
