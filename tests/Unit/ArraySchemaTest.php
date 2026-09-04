<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Schemas\ArraySchema;
use PHPUnit\Framework\TestCase;

final class ArraySchemaTest extends TestCase
{
    public function testNonArrayInputFails(): void
    {
        $schema = new ArraySchema();
        $failed = $schema->safeParse('not-array');
        $this->assertTrue($failed->failed());
        $this->assertSame('O campo :attribute deve ser um array.', $failed->firstError());
    }

    public function testMinMaxAndNonEmpty(): void
    {
        $schema = (new ArraySchema())
            ->min(2)
            ->max(4);

        $this->assertTrue($schema->safeParse(['a', 'b'])->isValid());
        $this->assertTrue($schema->safeParse(['a', 'b', 'c', 'd'])->isValid());

        $tooShort = $schema->safeParse(['a']);
        $this->assertTrue($tooShort->failed());
        $this->assertSame('O campo :attribute deve conter ao menos 2 itens.', $tooShort->firstError());

        $tooLong = $schema->safeParse(['a', 'b', 'c', 'd', 'e']);
        $this->assertTrue($tooLong->failed());
        $this->assertSame('O campo :attribute não pode conter mais de 4 itens.', $tooLong->firstError());

        $nonEmptySchema = (new ArraySchema())->nonEmpty();
        $this->assertTrue($nonEmptySchema->safeParse([])->failed());
    }

    public function testArrayWithoutItemSchema(): void
    {
        $schema = new ArraySchema();
        $this->assertSame(['x', 10, true], $schema->parse(['x', 10, true]));
    }

    public function testArrayWithItemSchema(): void
    {
        $schema = AV::array(AV::string()->email());

        $valid = $schema->safeParse(['test1@example.com', 'test2@example.com']);
        $this->assertTrue($valid->isValid());

        $invalid = $schema->safeParse(['valid@example.com', 'invalid-email']);
        $this->assertTrue($invalid->failed());
        $this->assertTrue($invalid->hasError('1'));
    }
}
