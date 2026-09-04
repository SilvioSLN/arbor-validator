<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Schemas\FloatSchema;
use Arbor\Validator\Schemas\IntSchema;
use Arbor\Validator\Schemas\NumberSchema;
use PHPUnit\Framework\TestCase;

final class NumberSchemaTest extends TestCase
{
    public function testNumberSchemaValidation(): void
    {
        $schema = (new NumberSchema())
            ->min(10, 'Valor muito baixo')
            ->max(100, 'Valor muito alto')
            ->positive('Deve ser positivo');

        $this->assertTrue($schema->safeParse(50)->isValid());

        $minFail = $schema->safeParse(5);
        $this->assertTrue($minFail->failed());
        $this->assertSame('Valor muito baixo', $minFail->firstError());

        $maxFail = $schema->safeParse(150);
        $this->assertTrue($maxFail->failed());
        $this->assertSame('Valor muito alto', $maxFail->firstError());

        $negFail = $schema->safeParse(-5);
        $this->assertTrue($negFail->failed());

        // Teste de número negativo
        $negSchema = (new NumberSchema())->negative('Deve ser negativo');
        $this->assertTrue($negSchema->safeParse(-10)->isValid());
        $this->assertTrue($negSchema->safeParse(10)->failed());
        $this->assertSame('Deve ser negativo', $negSchema->safeParse(10)->firstError());
    }

    public function testDefaultErrorMessages(): void
    {
        $schema = (new NumberSchema())->min(10)->max(50)->positive();
        $this->assertSame('O campo :attribute deve ser no mínimo 10.', $schema->safeParse(5)->firstError());
        $this->assertSame('O campo :attribute deve ser no máximo 50.', $schema->safeParse(60)->firstError());

        $negSchema = (new NumberSchema())->negative();
        $this->assertSame('O campo :attribute deve ser menor que zero.', $negSchema->safeParse(5)->firstError());

        $notNum = $schema->safeParse('texto');
        $this->assertSame('O campo :attribute deve ser um número.', $notNum->firstError());
    }

    public function testCoercionAndTypeConversion(): void
    {
        $schema = (new NumberSchema())->coerce();
        $this->assertSame(42.5, $schema->parse('42.5'));

        // Conversão para IntSchema e FloatSchema mantendo configurações
        $num = (new NumberSchema())
            ->min(5)
            ->max(20)
            ->positive()
            ->coerce()
            ->optional()
            ->nullable();

        $intSchema = $num->int();
        $this->assertInstanceOf(IntSchema::class, $intSchema);
        $this->assertTrue((new IntSchema())->safeParse(10.5)->failed()); // Não é int sem coerce
        $this->assertSame(10, $intSchema->parse(10.5)); // Com coerce vira 10

        $floatSchema = $num->float();
        $this->assertInstanceOf(FloatSchema::class, $floatSchema);
        $this->assertSame(10.0, $floatSchema->parse('10'));
        $this->assertTrue($floatSchema->safeParse('abc')->failed());

        $negativeNum = (new NumberSchema())->negative();
        $this->assertInstanceOf(IntSchema::class, $negativeNum->int());
        $this->assertInstanceOf(FloatSchema::class, $negativeNum->float());
    }
}
