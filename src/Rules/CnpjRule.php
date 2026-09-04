<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class CnpjRule implements RuleInterface
{
    public function __construct(
        public readonly bool $allowAlphanumeric = true,
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
        if (!self::isValid($str, $this->allowAlphanumeric)) {
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

    public static function isValid(string $cnpj, bool $allowAlphanumeric = true): bool
    {
        $clean = self::unmask($cnpj);

        if (strlen($clean) !== 14) {
            return false;
        }

        // Rejeita sequências onde todos os 14 caracteres são iguais
        if (preg_match('/^(.)\1{13}$/', $clean)) {
            return false;
        }

        $hasLetters = (bool) preg_match('/[A-Z]/', $clean);
        if ($hasLetters && !$allowAlphanumeric) {
            return false;
        }

        // As primeiras 12 posições devem ser alfanuméricas maiúsculas (0-9 ou A-Z)
        // As duas últimas posições DEVEM ser estritamente numéricas (0-9)
        if (!preg_match('/^[0-9A-Z]{12}[0-9]{2}$/', $clean)) {
            return false;
        }

        // Pesos para DV1 e DV2
        $weightsDv1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weightsDv2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        // Cálculo do 1º Dígito Verificador (Normativa RFB 2.229/2024: ord(char) - 48)
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $val = ord($clean[$i]) - 48;
            $sum += $val * $weightsDv1[$i];
        }
        $remainder = $sum % 11;
        $dv1 = ($remainder < 2) ? 0 : 11 - $remainder;
        if (((int) $clean[12]) !== $dv1) {
            return false;
        }

        // Cálculo do 2º Dígito Verificador
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $val = ord($clean[$i]) - 48;
            $sum += $val * $weightsDv2[$i];
        }
        $sum += $dv1 * $weightsDv2[12]; // $weightsDv2[12] é 2
        $remainder = $sum % 11;
        $dv2 = ($remainder < 2) ? 0 : 11 - $remainder;
        if (((int) $clean[13]) !== $dv2) {
            return false;
        }

        return true;
    }

    public static function unmask(string $cnpj): string
    {
        // Remove '.', '/', '-' e espaços, mantendo letras e dígitos em maiúsculo
        $cleaned = preg_replace('/[\.\/\-\s]/', '', $cnpj) ?? '';
        return strtoupper($cleaned);
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('cnpj');
        }
    }
}
