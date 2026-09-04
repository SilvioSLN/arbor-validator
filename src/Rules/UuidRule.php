<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class UuidRule implements RuleInterface
{
    public function __construct(
        public readonly int $version = 0,
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

        if (!self::isValid($value, $this->version)) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    public static function isValid(string $uuid, int $version = 0): bool
    {
        if ($version === 0) {
            return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
        }

        $v = dechex($version);
        return (bool) preg_match("/^[0-9a-f]{8}-[0-9a-f]{4}-{$v}[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i", $uuid);
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('uuid');
        }
    }
}
