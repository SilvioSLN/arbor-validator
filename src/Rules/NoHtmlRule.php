<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class NoHtmlRule implements RuleInterface
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

    public static function isValid(string $text): bool
    {
        // Se após strip_tags o texto mudou, havia tags HTML
        if (strip_tags($text) !== $text) {
            return false;
        }

        // Checagem adicional contra tags maliciosas incompletas ou vetores XSS (<script, <img, etc)
        if (preg_match('/<\s*[a-zA-Z\/!]/i', $text)) {
            return false;
        }

        return true;
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('no_html');
        }
    }
}
