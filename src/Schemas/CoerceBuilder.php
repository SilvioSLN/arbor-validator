<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

/**
 * Fluent builder for creating schemas with automatic type coercion enabled.
 * Returned by AV::coerce().
 *
 * @api
 */
final class CoerceBuilder
{
    /**
     * Creates a string schema that automatically casts scalar values to string.
     */
    public function string(): StringSchema
    {
        return (new StringSchema())->transform(fn($v) => is_scalar($v) ? (string) $v : $v);
    }

    /**
     * Creates a number schema that automatically coerces numeric strings to float/int.
     */
    public function number(): NumberSchema
    {
        return (new NumberSchema())->coerce();
    }

    /**
     * Creates an integer schema that automatically coerces numeric strings or floats to int.
     */
    public function int(): IntSchema
    {
        return (new IntSchema())->coerce();
    }

    /**
     * Creates a float schema that automatically coerces numeric strings (handling '.' and ',') to float.
     */
    public function float(): FloatSchema
    {
        return (new FloatSchema())->coerce();
    }

    /**
     * Creates a boolean schema that automatically coerces truthy/falsy strings/ints to bool.
     * Recognized truthy: 'true', '1', 'on', 'yes', 's', 'sim', 1.
     * Recognized falsy: 'false', '0', 'off', 'no', 'n', 'não', 'nao', '', 0.
     */
    public function bool(): BoolSchema
    {
        return (new BoolSchema())->coerce();
    }

    /**
     * Creates a date schema that validates against the specified format and coerces to \DateTimeImmutable.
     */
    public function date(string $format = 'Y-m-d'): StringSchema
    {
        return (new StringSchema())->date($format)->coerceDate($format);
    }
}
