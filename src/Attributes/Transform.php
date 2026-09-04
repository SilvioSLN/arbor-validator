<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Transform
{
    /**
     * @var callable|string
     */
    public readonly mixed $transformer;

    /**
     * @param callable|string $transformer
     */
    public function __construct(callable|string $transformer)
    {
        $this->transformer = $transformer;
    }

    public function transform(mixed $value): mixed
    {
        return ($this->transformer)($value);
    }
}
