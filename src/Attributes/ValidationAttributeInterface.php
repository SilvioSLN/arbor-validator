<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;

interface ValidationAttributeInterface
{
    public function validate(mixed $value, ValidationContext $context): bool;
}
