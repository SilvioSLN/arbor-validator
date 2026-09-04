<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class CpfRule implements RuleInterface
{
    public function __construct(
        public readonly bool $stripMask = false,
        public readonly ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_scalar($value)) {
            $this->fail($context);
            return false;
        }

        $str = (string) $value;
        if (!self::isValid($str)) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    public function sanitize(mixed $value): mixed
    {
        if ($this->stripMask && is_scalar($value)) {
            return self::unmask((string) $value);
        }

        return $value;
    }

    public static function isValid(string $cpf): bool
    {
        $digits = self::unmask($cpf);

        if (strlen($digits) !== 11) {
            return false;
        }

        // Rejeita sequências repetidas (000.000.000-00, 111.111.111-11, etc)
        if (preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        // Validação do 1º Dígito Verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $digits[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $dv1 = ($remainder < 2) ? 0 : 11 - $remainder;
        if (((int) $digits[9]) !== $dv1) {
            return false;
        }

        // Validação do 2º Dígito Verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int) $digits[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $dv2 = ($remainder < 2) ? 0 : 11 - $remainder;
        if (((int) $digits[10]) !== $dv2) {
            return false;
        }

        return true;
    }

    public static function unmask(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf) ?? '';
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('cpf');
        }
    }
}
