<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Schemas\EnumSchema;
use PHPUnit\Framework\TestCase;

enum FixtureRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';
}

final class EnumSchemaTest extends TestCase
{
    public function testArrayEnumValidation(): void
    {
        $schema = new EnumSchema(['pendente', 'aprovado', 'cancelado']);

        $this->assertTrue($schema->safeParse('aprovado')->isValid());
        $this->assertSame('aprovado', $schema->parse('aprovado'));

        $failed = $schema->safeParse('invalido');
        $this->assertTrue($failed->failed());
        $this->assertSame('O valor selecionado para :attribute é inválido. Opções permitidas: pendente, aprovado, cancelado.', $failed->firstError());
    }

    public function testBackedEnumClassValidation(): void
    {
        $schema = AV::enum(FixtureRole::class);

        $this->assertTrue($schema->safeParse('admin')->isValid());
        $this->assertTrue($schema->safeParse('editor')->isValid());

        $failed = $schema->safeParse('superuser');
        $this->assertTrue($failed->failed());
    }
}
