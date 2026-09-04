<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Schemas\ShapeSchema;
use PHPUnit\Framework\TestCase;

final class ShapeSchemaCompleteTest extends TestCase
{
    public function testNonArrayInputFails(): void
    {
        $schema = new ShapeSchema(['x' => AV::string()]);
        $failed = $schema->safeParse('not-an-array');
        $this->assertTrue($failed->failed());
        $this->assertSame('O campo :attribute deve ser um array.', $failed->firstError());
    }

    public function testStrictModeRejectsUnknownFields(): void
    {
        $schema = AV::shape([
            'nome' => AV::string(),
        ])->strict();

        $valid = $schema->safeParse(['nome' => 'Silvio']);
        $this->assertTrue($valid->isValid());

        $invalid = $schema->safeParse([
            'nome' => 'Silvio',
            'campo_estranho' => 'hacker',
        ]);
        $this->assertTrue($invalid->failed());
        $this->assertTrue($invalid->hasError('campo_estranho'));
        $this->assertSame('Campo não reconhecido: campo_estranho', $invalid->error('campo_estranho'));

        // Se voltar para strip(), campos extras são descartados sem erro
        $strippedSchema = $schema->strip();
        $resStrip = $strippedSchema->safeParse([
            'nome' => 'Silvio',
            'campo_estranho' => 'hacker',
        ]);
        $this->assertTrue($resStrip->isValid());
        $this->assertArrayNotHasKey('campo_estranho', $resStrip->data());
    }

    public function testMergeShapes(): void
    {
        $shapeA = AV::shape(['a' => AV::string()]);
        $shapeB = AV::shape(['b' => AV::number()]);

        $merged = $shapeA->merge($shapeB);
        $this->assertArrayHasKey('a', $merged->getFields());
        $this->assertArrayHasKey('b', $merged->getFields());

        $data = $merged->parse(['a' => 'texto', 'b' => 123]);
        $this->assertSame('texto', $data['a']);
        $this->assertSame(123, $data['b']);
    }
}
