<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\PhoneRule;
use PHPUnit\Framework\TestCase;

final class PhoneRuleTest extends TestCase
{
    public function testValidBrMobilePhones(): void
    {
        $phones = [
            '(11) 98765-4321',
            '11987654321',
            '+5511987654321',
            '5511987654321',
            '(21) 99887-7665',
        ];

        foreach ($phones as $phone) {
            $this->assertTrue(PhoneRule::isValidBr($phone), "Telefone {$phone} deveria ser válido");
            $context = new ValidationContext('phone');
            $rule = new PhoneRule();
            $this->assertTrue($rule->validate($phone, $context));
            $this->assertTrue($context->errorBag->isEmpty());
        }
    }

    public function testValidBrLandlinePhones(): void
    {
        $landlines = [
            '(11) 3456-7890',
            '1134567890',
            '+551123456789',
        ];

        foreach ($landlines as $phone) {
            $this->assertTrue(PhoneRule::isValidBr($phone), "Fixo {$phone} deveria ser válido");
        }
    }

    public function testInvalidBrPhones(): void
    {
        $invalid = [
            '1187654321', // Celular com 8 dígitos sem o nono dígito 9
            '01987654321', // DDD 01 inexistente
            '11999999999', // Todos os dígitos do número repetidos
            '1234',
        ];

        foreach ($invalid as $phone) {
            $this->assertFalse(PhoneRule::isValidBr($phone), "Telefone {$phone} deveria ser inválido");
            $context = new ValidationContext('phone');
            $rule = new PhoneRule();
            $this->assertFalse($rule->validate($phone, $context));
            $this->assertTrue($context->errorBag->has('phone'));
        }
    }

    public function testStripMaskOption(): void
    {
        $phone = '(11) 98765-4321';
        $rule = new PhoneRule(stripMask: true);
        $this->assertSame('11987654321', $rule->sanitize($phone));
    }
}
