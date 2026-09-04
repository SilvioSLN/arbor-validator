<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

interface RuleInterface
{
    /**
     * Valida o valor dentro do contexto.
     * Retorna true se válido, ou false se inválido (adicionando erro no contexto).
     */
    public function validate(mixed $value, ValidationContext $context): bool;
}
