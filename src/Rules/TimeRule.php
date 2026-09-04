<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class TimeRule implements RuleInterface
{
    public function __construct(
        public readonly string $format = 'H:i',
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
        if (!self::isValid($str, $this->format)) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    public static function isValid(string $time, string $format = 'H:i'): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!' . $format, $time);
        if ($d === false) {
            return false;
        }

        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return false;
        }

        return $d->format($format) === $time;
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('time', ['format' => $this->format]);
        }
    }
}
