<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Coerce
{
    public function __construct(
        public ?string $type = null,
        public ?string $dateFormat = null,
    ) {
    }
}
