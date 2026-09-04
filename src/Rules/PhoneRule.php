<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class PhoneRule implements RuleInterface
{
    private const array VALID_BR_DDDS = [
        '11', '12', '13', '14', '15', '16', '17', '18', '19',
        '21', '22', '24', '27', '28',
        '31', '32', '33', '34', '35', '37', '38',
        '41', '42', '43', '44', '45', '46', '47', '48', '49',
        '51', '53', '54', '55',
        '61', '62', '63', '64', '65', '66', '67', '68', '69',
        '71', '73', '74', '75', '77', '79',
        '81', '82', '83', '84', '85', '86', '87', '88', '89',
        '91', '92', '93', '94', '95', '96', '97', '98', '99',
    ];

    public function __construct(
        public readonly string $country = 'BR',
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
        if (!self::isValid($str, $this->country)) {
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

    public static function isValid(string $phone, string $country = 'BR'): bool
    {
        $countryUpper = strtoupper($country);

        if ($countryUpper === 'BR') {
            return self::isValidBr($phone);
        }

        // Validação genérica internacional E.164 (+123456789 até 15 dígitos)
        $clean = preg_replace('/[^\d+]/', '', $phone) ?? '';
        return (bool) preg_match('/^\+?[1-9]\d{6,14}$/', $clean);
    }

    public static function isValidBr(string $phone): bool
    {
        $clean = self::unmask($phone);

        // Remove prefixo DDI do Brasil se informado (+55 ou 55)
        if (str_starts_with($clean, '55') && (strlen($clean) === 12 || strlen($clean) === 13)) {
            $clean = substr($clean, 2);
        }

        $len = strlen($clean);
        // Telefone fixo (10 dígitos) ou Celular (11 dígitos)
        if ($len !== 10 && $len !== 11) {
            return false;
        }

        $ddd = substr($clean, 0, 2);
        if (!in_array($ddd, self::VALID_BR_DDDS, true)) {
            return false;
        }

        // Não permitir todos os números repetidos (ex: 11999999999)
        $numberPart = substr($clean, 2);
        if (preg_match('/^(\d)\1+$/', $numberPart)) {
            return false;
        }

        if ($len === 11) {
            // Celular: 9º dígito deve ser '9'
            return $clean[2] === '9';
        }

        // Fixo: primeiro dígito após DDD deve ser 2, 3, 4 ou 5
        return in_array($clean[2], ['2', '3', '4', '5'], true);
    }

    public static function unmask(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('phone');
        }
    }
}
