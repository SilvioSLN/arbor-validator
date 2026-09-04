<?php

declare(strict_types=1);

namespace Arbor\Validator;

use Arbor\Validator\Schemas\ArraySchema;
use Arbor\Validator\Schemas\BoolSchema;
use Arbor\Validator\Schemas\CoerceBuilder;
use Arbor\Validator\Schemas\EnumSchema;
use Arbor\Validator\Schemas\FileSchema;
use Arbor\Validator\Schemas\FloatSchema;
use Arbor\Validator\Schemas\IntSchema;
use Arbor\Validator\Schemas\NumberSchema;
use Arbor\Validator\Schemas\PreprocessSchema;
use Arbor\Validator\Schemas\Schema;
use Arbor\Validator\Schemas\ShapeSchema;
use Arbor\Validator\Schemas\StringSchema;

/**
 * Fluent schema builder factory (Zod-like API).
 *
 * @api
 */
final class AV
{
    /**
     * Creates a string validation schema.
     */
    public static function string(): StringSchema
    {
        return new StringSchema();
    }

    /**
     * Creates a generic number validation schema (accepts int or float).
     */
    public static function number(): NumberSchema
    {
        return new NumberSchema();
    }

    /**
     * Creates an integer validation schema.
     */
    public static function int(): IntSchema
    {
        return new IntSchema();
    }

    /**
     * Creates a float/decimal validation schema.
     */
    public static function float(): FloatSchema
    {
        return new FloatSchema();
    }

    /**
     * Creates a boolean validation schema.
     */
    public static function bool(): BoolSchema
    {
        return new BoolSchema();
    }

    /**
     * Alias for bool(). Creates a boolean validation schema.
     */
    public static function boolean(): BoolSchema
    {
        return self::bool();
    }

    /**
     * Creates an associative object/shape validation schema.
     *
     * @param array<string, Schema> $fields
     */
    public static function shape(array $fields): ShapeSchema
    {
        return new ShapeSchema($fields);
    }

    /**
     * Creates an array/list validation schema with optional item schema.
     */
    public static function array(?Schema $schema = null): ArraySchema
    {
        return new ArraySchema($schema);
    }

    /**
     * Creates an enum validation schema from a list of allowed values or a BackedEnum class name.
     *
     * @param list<string|int|float>|class-string<\BackedEnum> $cases
     */
    public static function enum(array|string $cases): EnumSchema
    {
        return new EnumSchema($cases);
    }

    /**
     * Creates an uploaded file validation schema.
     */
    public static function file(): FileSchema
    {
        return new FileSchema();
    }

    /**
     * Preprocesses an input value through a callable before passing to the inner schema.
     *
     * @param callable(mixed): mixed $fn
     */
    public static function preprocess(callable $fn, Schema $schema): PreprocessSchema
    {
        return new PreprocessSchema($fn, $schema);
    }

    /**
     * Returns a fluent builder for creating schemas with automatic coercion active.
     */
    public static function coerce(): CoerceBuilder
    {
        return new CoerceBuilder();
    }
}
