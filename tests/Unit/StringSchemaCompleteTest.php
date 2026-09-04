<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Schemas\StringSchema;
use PHPUnit\Framework\TestCase;

final class StringSchemaCompleteTest extends TestCase
{
    public function testNonScalarInputFails(): void
    {
        $schema = new StringSchema();
        $this->assertTrue($schema->safeParse(['array'])->failed());
    }

    public function testStringableObjectPasses(): void
    {
        $stringable = new class {
            public function __toString(): string
            {
                return 'texto stringable';
            }
        };

        $schema = new StringSchema();
        $this->assertSame('texto stringable', $schema->parse($stringable));
    }

    public function testExactLengthValidation(): void
    {
        $schema = (new StringSchema())->length(5, 'Tamanho deve ser 5');
        $this->assertTrue($schema->safeParse('12345')->isValid());

        $fail = $schema->safeParse('1234');
        $this->assertTrue($fail->failed());
        $this->assertSame('Tamanho deve ser 5', $fail->firstError());

        // Mensagem padrão
        $defaultMsgSchema = (new StringSchema())->length(3);
        $this->assertSame('O campo :attribute deve conter exatamente 3 caracteres.', $defaultMsgSchema->safeParse('12')->firstError());
    }

    public function testRegexValidation(): void
    {
        $schema = (new StringSchema())->regex('/^[A-Z]{3}$/', 'Deve ter 3 letras maiúsculas');
        $this->assertTrue($schema->safeParse('ABC')->isValid());

        $fail = $schema->safeParse('abc');
        $this->assertTrue($fail->failed());
        $this->assertSame('Deve ter 3 letras maiúsculas', $fail->firstError());

        $defaultMsg = (new StringSchema())->regex('/^[0-9]+$/');
        $this->assertSame('O formato do campo :attribute é inválido.', $defaultMsg->safeParse('abc')->firstError());
    }

    public function testUppercaseAndStripMask(): void
    {
        $schema = (new StringSchema())->uppercase();
        $this->assertSame('HELLO WORLD', $schema->parse('hello world'));

        $stripSchema = (new StringSchema())->stripMask();
        $this->assertSame('12345678900', $stripSchema->parse('123.456.789-00'));
    }

    public function testAllStringRulesChained(): void
    {
        $domainSchema = AV::string()->domain();
        $this->assertTrue($domainSchema->safeParse('arborphp.org')->isValid());
        $this->assertTrue($domainSchema->safeParse('invalid-domain')->failed());

        $urlSchema = AV::string()->url();
        $this->assertTrue($urlSchema->safeParse('https://example.com')->isValid());
        $this->assertTrue($urlSchema->safeParse('ftp://example.com')->failed());

        $uuidSchema = AV::string()->uuid(4);
        $this->assertTrue($uuidSchema->safeParse('550e8400-e29b-41d4-a716-446655440000')->isValid());
        $this->assertTrue($uuidSchema->safeParse('invalid')->failed());

        $noHtmlSchema = AV::string()->noHtml();
        $this->assertTrue($noHtmlSchema->safeParse('Apenas texto')->isValid());
        $this->assertTrue($noHtmlSchema->safeParse('<script>')->failed());

        $htmlSchema = AV::string()->html(sanitize: true);
        $this->assertTrue($htmlSchema->safeParse('<p>Paragrafo</p>')->isValid());
        $this->assertTrue($htmlSchema->safeParse('texto comum sem tag')->failed());
        $this->assertSame('<p>Limpo</p>evil()', $htmlSchema->parse('<p>Limpo</p><script>evil()</script>'));

        $timeSchema = AV::string()->time('H:i');
        $this->assertTrue($timeSchema->safeParse('18:45')->isValid());
        $this->assertTrue($timeSchema->safeParse('25:00')->failed());

        $emojisSchema = AV::string()->emojis(allow: true, only: true);
        $this->assertTrue($emojisSchema->safeParse('🔥🚀')->isValid());
        $this->assertTrue($emojisSchema->safeParse('texto')->failed());
    }
}
