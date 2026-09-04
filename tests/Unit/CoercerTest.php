<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\Coercer;
use PHPUnit\Framework\TestCase;

final class CoercerTest extends TestCase
{
    public function testNumericCoercion(): void
    {
        $this->assertSame(42, Coercer::toInt('42'));
        $this->assertSame(42, Coercer::toInt(42));
        $this->assertSame(12.34, Coercer::toFloat('12.34'));
        $this->assertSame(12.34, Coercer::toFloat('12,34'));
    }

    public function testBoolCoercion(): void
    {
        $this->assertTrue(Coercer::toBool('true'));
        $this->assertTrue(Coercer::toBool('1'));
        $this->assertTrue(Coercer::toBool('on'));
        $this->assertTrue(Coercer::toBool('yes'));
        $this->assertTrue(Coercer::toBool('sim'));

        $this->assertFalse(Coercer::toBool('false'));
        $this->assertFalse(Coercer::toBool('0'));
        $this->assertFalse(Coercer::toBool('off'));
        $this->assertFalse(Coercer::toBool('no'));
        $this->assertFalse(Coercer::toBool('nao'));
    }

    public function testNullableEmptyStringToNull(): void
    {
        $this->assertNull(Coercer::coerce('', 'string', isNullable: true));
        $this->assertNull(Coercer::coerce(null, 'int', isNullable: true));
        $this->assertSame('', Coercer::coerce('', 'string', isNullable: false));
    }

    public function testDateTimeImmutableCoercion(): void
    {
        $date = Coercer::toDateTimeImmutable('2026-09-02', 'Y-m-d');
        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertSame('2026-09-02', $date->format('Y-m-d'));
    }
}
