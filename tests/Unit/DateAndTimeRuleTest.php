<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\DateRule;
use Arbor\Validator\Rules\TimeRule;
use PHPUnit\Framework\TestCase;

final class DateAndTimeRuleTest extends TestCase
{
    public function testValidAndInvalidDates(): void
    {
        $this->assertTrue(DateRule::isValid('2026-09-02', 'Y-m-d'));
        $this->assertTrue(DateRule::isValid('2024-02-29', 'Y-m-d')); // Ano bissexto válido

        // Datas impossíveis
        $this->assertFalse(DateRule::isValid('2023-02-29', 'Y-m-d')); // Não é bissexto
        $this->assertFalse(DateRule::isValid('2024-02-30', 'Y-m-d'));
        $this->assertFalse(DateRule::isValid('2024-13-01', 'Y-m-d'));
        $this->assertFalse(DateRule::isValid('02/09/2026', 'Y-m-d')); // Formato errado
    }

    public function testValidAndInvalidTimes(): void
    {
        $this->assertTrue(TimeRule::isValid('14:30', 'H:i'));
        $this->assertTrue(TimeRule::isValid('00:00', 'H:i'));
        $this->assertTrue(TimeRule::isValid('23:59', 'H:i'));

        // Horários impossíveis
        $this->assertFalse(TimeRule::isValid('24:00', 'H:i'));
        $this->assertFalse(TimeRule::isValid('14:60', 'H:i'));
        $this->assertFalse(TimeRule::isValid('9:00', 'H:i')); // Falta dígito zero
    }
}
