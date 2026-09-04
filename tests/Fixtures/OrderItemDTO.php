<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Fixtures;

use Arbor\Validator\Attributes as V;

final readonly class OrderItemDTO
{
    public function __construct(
        #[V\Required]
        public string $product,

        #[V\Required]
        public int $quantity,

        #[V\Required]
        public float $price,
    ) {
    }
}
