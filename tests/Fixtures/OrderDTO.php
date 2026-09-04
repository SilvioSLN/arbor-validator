<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Fixtures;

use Arbor\Validator\Attributes as V;

final readonly class OrderDTO
{
    /**
     * @param OrderItemDTO[] $items
     */
    public function __construct(
        #[V\Required]
        public string $customer,

        #[V\Required, V\Nested(AddressDTO::class)]
        public AddressDTO $address,

        #[V\Required, V\Each(OrderItemDTO::class)]
        public array $items,
    ) {
    }
}
