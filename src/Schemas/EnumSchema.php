<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\ValidationContext;

class EnumSchema extends Schema
{
    /**
     * @var list<string|int|float>
     */
    protected array $allowedValues;

    /**
     * @param array<array-key, string|int|float>|class-string<\BackedEnum> $cases
     */
    public function __construct(array|string $cases)
    {
        if (is_string($cases) && enum_exists($cases)) {
            $this->allowedValues = array_map(fn($c) => $c->value, $cases::cases());
        } elseif (is_array($cases)) {
            $this->allowedValues = array_values($cases);
        } else {
            $this->allowedValues = [];
        }
    }

    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        if (!in_array($value, $this->allowedValues, true)) {
            $context->addErrorByKey('enum', [
                'values' => implode(', ', array_map('strval', $this->allowedValues)),
            ]);
            return $value;
        }

        return $value;
    }
}
