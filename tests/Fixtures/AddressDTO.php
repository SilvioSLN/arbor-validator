<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Fixtures;

use Arbor\Validator\Attributes as V;

final readonly class AddressDTO
{
    public function __construct(
        #[V\Required]
        public string $street,

        #[V\Required]
        public string $city,

        #[V\Optional]
        public ?string $zip = null,
    ) {
    }
}
