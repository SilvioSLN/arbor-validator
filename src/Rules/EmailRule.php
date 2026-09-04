<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class EmailRule implements RuleInterface
{
    public function __construct(
        public readonly bool $checkDns = false,
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

        $trimmed = trim($value);
        if (!self::isValid($trimmed, $this->checkDns)) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    public function sanitize(mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }

    public static function isValid(string $email, bool $checkDns = false): bool
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        // Garante que o domínio possui ao menos um ponto
        $parts = explode('@', $email);
        if (count($parts) !== 2 || !str_contains($parts[1], '.')) {
            return false;
        }

        if ($checkDns && function_exists('checkdnsrr')) {
            return checkdnsrr($parts[1], 'MX') || checkdnsrr($parts[1], 'A');
        }

        return true;
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('email');
        }
    }
}
