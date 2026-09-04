<?php

declare(strict_types=1);

namespace Arbor\Validator\Core;

class Coercer
{
    /**
     * Coerção inteligente baseada no nome de tipo nativo ou classe.
     */
    public static function coerce(mixed $value, ?string $targetType, bool $isNullable = false, ?string $dateFormat = null): mixed
    {
        // Se for string vazia e campo for anulável, converte para null
        if ($isNullable && ($value === null || $value === '')) {
            return null;
        }

        if ($targetType === null) {
            return $value;
        }

        return match ($targetType) {
            'int' => self::toInt($value),
            'float' => self::toFloat($value),
            'bool' => self::toBool($value),
            'string' => self::toString($value),
            'array' => self::toArray($value),
            \DateTimeImmutable::class => self::toDateTimeImmutable($value, $dateFormat),
            \DateTime::class => self::toDateTime($value, $dateFormat),
            default => $value,
        };
    }

    public static function toInt(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }

    public static function toFloat(mixed $value): mixed
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = str_replace(',', '.', trim($value));
            if (is_numeric($normalized)) {
                return (float) $normalized;
            }
        }

        return $value;
    }

    public static function toBool(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if (in_array($lower, ['true', '1', 'on', 'yes', 's', 'sim'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'off', 'no', 'n', 'não', 'nao', ''], true)) {
                return false;
            }
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        return (bool) $value;
    }

    public static function toString(mixed $value): mixed
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return (string) $value;
        }

        return $value;
    }

    public static function toArray(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        return [$value];
    }

    public static function toDateTimeImmutable(mixed $value, ?string $format = null): mixed
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($value);
        }

        if (!is_string($value) || trim($value) === '') {
            return $value;
        }

        $str = trim($value);

        if ($format !== null) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $str);
            if ($date !== false) {
                return $date;
            }
        }

        try {
            return new \DateTimeImmutable($str);
        } catch (\Exception) {
            return $value;
        }
    }

    public static function toDateTime(mixed $value, ?string $format = null): mixed
    {
        if ($value instanceof \DateTime) {
            return $value;
        }

        $immutable = self::toDateTimeImmutable($value, $format);
        if ($immutable instanceof \DateTimeImmutable) {
            return \DateTime::createFromImmutable($immutable);
        }

        return $value;
    }
}
