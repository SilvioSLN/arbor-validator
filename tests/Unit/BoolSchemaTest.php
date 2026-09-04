<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Schemas\BoolSchema;
use PHPUnit\Framework\TestCase;

final class BoolSchemaTest extends TestCase
{
    public function testStrictBoolValidation(): void
    {
        $schema = new BoolSchema();

        $this->assertTrue($schema->safeParse(true)->isValid());
        $this->assertTrue($schema->safeParse(false)->isValid());

        $failed = $schema->safeParse('true');
        $this->assertTrue($failed->failed());
        $this->assertSame('O campo :attribute deve ser verdadeiro ou falso.', $failed->firstError());
    }

    public function testCoercedBoolValidation(): void
    {
        $schema = (new BoolSchema())->coerce();

        $this->assertTrue($schema->parse('true'));
        $this->assertTrue($schema->parse('1'));
        $this->assertTrue($schema->parse('yes'));

        $this->assertFalse($schema->parse('false'));
        $this->assertFalse($schema->parse('0'));
        $this->assertFalse($schema->parse('off'));
    }
}
