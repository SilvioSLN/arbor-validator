<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\FullNameRule;
use PHPUnit\Framework\TestCase;

final class FullNameRuleTest extends TestCase
{
    public function testValidFullNames(): void
    {
        $valid = [
            'Silvio Silva',
            'Maria Madalena dos Santos',
            'Ana Paula',
            'José de Alencar',
        ];

        foreach ($valid as $name) {
            $this->assertTrue(FullNameRule::isValid($name));
            $context = new ValidationContext('name');
            $rule = new FullNameRule();
            $this->assertTrue($rule->validate($name, $context));
        }
    }

    public function testSingleWordAndAbbreviatedNamesAreRejected(): void
    {
        $invalid = [
            'Silvio', // Apenas uma palavra
            'Ana S.', // Sobrenome abreviado com 1 letra
            'A B',    // Letras soltas
            '   ',    // Vazio
        ];

        foreach ($invalid as $name) {
            $this->assertFalse(FullNameRule::isValid($name), "Nome '{$name}' deveria ser rejeitado");
            $context = new ValidationContext('name');
            $rule = new FullNameRule();
            $this->assertFalse($rule->validate($name, $context));
            $this->assertTrue($context->errorBag->has('name'));
        }
    }
}
