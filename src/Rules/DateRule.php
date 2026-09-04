<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class DateRule implements RuleInterface
{
    public function __construct(
        public readonly string $format = 'Y-m-d',
        public readonly ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (!is_scalar($value)) {
            $this->fail($context);
            return false;
        }

        $str = (string) $value;
        if (!self::isValid($str, $this->format)) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    public static function isValid(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!' . $format, $date);
        if ($d === false) {
            return false;
        }

        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return false;
        }

        // Verifica se a formatação de volta coincide (evita overflow como 2024-02-31 virando 2024-03-02)
        return $d->format($format) === $date;
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('date', ['format' => $this->format]);
        }
    }
}
