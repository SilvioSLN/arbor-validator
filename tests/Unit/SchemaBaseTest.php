<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class SchemaBaseTest extends TestCase
{
    public function testNullableAndDefault(): void
    {
        $schema = AV::string()->nullable();
        $this->assertTrue($schema->isNullable());
        $this->assertNull($schema->parse(null));
        $this->assertNull($schema->parse(''));

        $defaultSchema = AV::string()->default('padrao');
        $this->assertSame('padrao', $defaultSchema->parse(null));
        $this->assertSame('padrao', $defaultSchema->parse(''));
        $this->assertSame('outro', $defaultSchema->parse('outro'));
    }

    public function testSuperRefine(): void
    {
        $schema = AV::string()->superRefine(function (string $val, ValidationContext $ctx) {
            if ($val === 'proibido') {
                $ctx->addError('Valor expressamente proibido');
            }
        });

        $this->assertTrue($schema->safeParse('permitido')->isValid());

        $failed = $schema->safeParse('proibido');
        $this->assertTrue($failed->failed());
        $this->assertSame('Valor expressamente proibido', $failed->firstError());
    }

    public function testCatchWithRequiredError(): void
    {
        $schema = AV::string()->catch('fallback');
        // Campo nulo sem optional gera required, mas catch deve capturar e retornar fallback
        $this->assertSame('fallback', $schema->parse(null));
        $this->assertSame('fallback', $schema->parse(''));
    }
}
