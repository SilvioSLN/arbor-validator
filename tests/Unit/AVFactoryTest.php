<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Schemas\ArraySchema;
use Arbor\Validator\Schemas\BoolSchema;
use Arbor\Validator\Schemas\EnumSchema;
use Arbor\Validator\Schemas\FileSchema;
use Arbor\Validator\Schemas\FloatSchema;
use Arbor\Validator\Schemas\IntSchema;
use Arbor\Validator\Schemas\NumberSchema;
use Arbor\Validator\Schemas\PreprocessSchema;
use Arbor\Validator\Schemas\ShapeSchema;
use Arbor\Validator\Schemas\StringSchema;
use PHPUnit\Framework\TestCase;

final class AVFactoryTest extends TestCase
{
    public function testFactoryMethodsInstantiateCorrectSchemas(): void
    {
        $this->assertInstanceOf(StringSchema::class, AV::string());
        $this->assertInstanceOf(NumberSchema::class, AV::number());
        $this->assertInstanceOf(IntSchema::class, AV::int());
        $this->assertInstanceOf(FloatSchema::class, AV::float());
        $this->assertInstanceOf(BoolSchema::class, AV::bool());
        $this->assertInstanceOf(BoolSchema::class, AV::boolean());
        $this->assertInstanceOf(ShapeSchema::class, AV::shape([]));
        $this->assertInstanceOf(ArraySchema::class, AV::array());
        $this->assertInstanceOf(EnumSchema::class, AV::enum(['a', 'b']));
        $this->assertInstanceOf(FileSchema::class, AV::file());
        $this->assertInstanceOf(PreprocessSchema::class, AV::preprocess(fn($x) => $x, AV::string()));
    }

    public function testCoerceFactoryBuilder(): void
    {
        $coerce = AV::coerce();
        $this->assertInstanceOf(StringSchema::class, $coerce->string());
        $this->assertInstanceOf(NumberSchema::class, $coerce->number());
        $this->assertInstanceOf(IntSchema::class, $coerce->int());
        $this->assertInstanceOf(FloatSchema::class, $coerce->float());
        $this->assertInstanceOf(BoolSchema::class, $coerce->bool());
        $this->assertInstanceOf(StringSchema::class, $coerce->date('Y-m-d'));

        // Testa execução da coerção pelo builder
        $this->assertSame('123', $coerce->string()->parse(123));
        $this->assertSame(42.5, $coerce->number()->parse('42.5'));
        $this->assertSame(42, $coerce->int()->parse('42'));
        $this->assertSame(42.0, $coerce->float()->parse('42'));
        $this->assertTrue($coerce->bool()->parse('1'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $coerce->date('Y-m-d')->parse('2026-09-02'));
    }
}
