<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class HtmlRule implements RuleInterface
{
    public function __construct(
        public readonly bool $sanitize = false,
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

    public static function isValid(string $html): bool
    {
        // Verifica se contém ao menos uma tag HTML válida estruturada
        return (bool) preg_match('/<[a-z][\s\S]*>/i', $html);
    }

    public function sanitize(mixed $value): mixed
    {
        if ($this->sanitize && is_string($value)) {
            // Remove scripts e tags perigosas
            return strip_tags($value, ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre', 'a', 'span']);
        }

        return $value;
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('html');
        }
    }
}
