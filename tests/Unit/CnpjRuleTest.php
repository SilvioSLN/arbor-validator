<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\CnpjRule;
use PHPUnit\Framework\TestCase;

final class CnpjRuleTest extends TestCase
{
    public function testValidTraditionalNumericCnpj(): void
    {
        $validCnpj = '11.222.333/0001-81';
        $unmasked = '11222333000181';

        $this->assertTrue(CnpjRule::isValid($validCnpj));
        $this->assertTrue(CnpjRule::isValid($unmasked));

        $context = new ValidationContext('cnpj');
        $rule = new CnpjRule();
        $this->assertTrue($rule->validate($validCnpj, $context));
        $this->assertTrue($context->errorBag->isEmpty());
    }

    public function testValidAlphanumericCnpjFromReceitaFederal(): void
    {
        // 12.ABC.345/01DE-35 é um CNPJ alfanumérico válido segundo a fórmula oficial (ord(c) - 48)
        $alphanumericCnpj = '12.ABC.345/01DE-35';
        $unmasked = '12ABC34501DE35';

        $this->assertTrue(CnpjRule::isValid($alphanumericCnpj, allowAlphanumeric: true));
        $this->assertTrue(CnpjRule::isValid($unmasked, allowAlphanumeric: true));

        // Deve falhar se allowAlphanumeric for false
        $this->assertFalse(CnpjRule::isValid($alphanumericCnpj, allowAlphanumeric: false));

        $context = new ValidationContext('cnpj');
        $rule = new CnpjRule(allowAlphanumeric: true);
        $this->assertTrue($rule->validate($alphanumericCnpj, $context));
        $this->assertTrue($context->errorBag->isEmpty());
    }

    public function testInvalidCnpjCheckDigitsAreRejected(): void
    {
        $invalidCnpj = '11.222.333/0001-82'; // DV2 incorreto (esperado 81)
        $this->assertFalse(CnpjRule::isValid($invalidCnpj));

        $context = new ValidationContext('cnpj');
        $rule = new CnpjRule();
        $this->assertFalse($rule->validate($invalidCnpj, $context));
        $this->assertTrue($context->errorBag->has('cnpj'));
    }

    public function testRepeatedCnpjCharactersAreRejected(): void
    {
        $repeated = [
            '00.000.000/0000-00',
            '11.111.111/1111-11',
            '00000000000000',
        ];

        foreach ($repeated as $cnpj) {
            $this->assertFalse(CnpjRule::isValid($cnpj));
        }
    }

    public function testStripMaskOption(): void
    {
        $validCnpj = '12.ABC.345/01DE-35';
        $ruleWithStrip = new CnpjRule(stripMask: true);
        $this->assertSame('12ABC34501DE35', $ruleWithStrip->sanitize($validCnpj));
    }
}
