<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\CpfRule;
use PHPUnit\Framework\TestCase;

final class CpfRuleTest extends TestCase
{
    public function testValidCpfWithAndWithoutMask(): void
    {
        // 111.444.777-35 é um CPF matematicamente válido
        $validCpf = '111.444.777-35';
        $unmasked = '11144477735';

        $this->assertTrue(CpfRule::isValid($validCpf));
        $this->assertTrue(CpfRule::isValid($unmasked));

        $context = new ValidationContext('cpf', ['cpf' => $validCpf]);
        $rule = new CpfRule();
        $this->assertTrue($rule->validate($validCpf, $context));
        $this->assertTrue($context->errorBag->isEmpty());
    }

    public function testRepeatedDigitsAreRejected(): void
    {
        $repeated = [
            '000.000.000-00',
            '111.111.111-11',
            '22222222222',
            '99999999999',
        ];

        foreach ($repeated as $cpf) {
            $this->assertFalse(CpfRule::isValid($cpf), "CPF {$cpf} não deveria ser válido");

            $context = new ValidationContext('cpf');
            $rule = new CpfRule();
            $this->assertFalse($rule->validate($cpf, $context));
            $this->assertTrue($context->errorBag->has('cpf'));
        }
    }

    public function testInvalidCheckDigitsAreRejected(): void
    {
        $invalidCpf = '111.444.777-36'; // DV2 incorreto (esperado 35)
        $this->assertFalse(CpfRule::isValid($invalidCpf));

        $context = new ValidationContext('cpf');
        $rule = new CpfRule();
        $this->assertFalse($rule->validate($invalidCpf, $context));
        $this->assertTrue($context->errorBag->has('cpf'));
    }

    public function testStripMaskOption(): void
    {
        $validCpf = '111.444.777-35';
        $ruleWithStrip = new CpfRule(stripMask: true);
        $this->assertSame('11144477735', $ruleWithStrip->sanitize($validCpf));

        $ruleWithoutStrip = new CpfRule(stripMask: false);
        $this->assertSame($validCpf, $ruleWithoutStrip->sanitize($validCpf));
    }
}
