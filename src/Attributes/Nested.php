<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Nested
{
    /**
     * @param class-string|null $dtoClass
     */
    public function __construct(
        public ?string $dtoClass = null,
    ) {
    }
}
