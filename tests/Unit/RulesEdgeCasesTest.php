<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Files\UploadedFile;
use Arbor\Validator\Rules\CnpjRule;
use Arbor\Validator\Rules\CpfRule;
use Arbor\Validator\Rules\DateRule;
use Arbor\Validator\Rules\DomainRule;
use Arbor\Validator\Rules\EmailRule;
use Arbor\Validator\Rules\EmojisRule;
use Arbor\Validator\Rules\FullNameRule;
use Arbor\Validator\Rules\HtmlRule;
use Arbor\Validator\Rules\NoHtmlRule;
use Arbor\Validator\Rules\PhoneRule;
use Arbor\Validator\Rules\TimeRule;
use Arbor\Validator\Rules\UploadedFileRule;
use Arbor\Validator\Rules\UrlRule;
use Arbor\Validator\Rules\UuidRule;
use PHPUnit\Framework\TestCase;

final class RulesEdgeCasesTest extends TestCase
{
    public function testNullAndEmptyValuesPassValidation(): void
    {
        $rules = [
            new CpfRule(),
            new CnpjRule(),
            new PhoneRule(),
            new FullNameRule(),
            new EmailRule(),
            new DateRule(),
            new TimeRule(),
            new DomainRule(),
            new UrlRule(),
            new UuidRule(),
            new NoHtmlRule(),
            new HtmlRule(),
            new EmojisRule(),
            new UploadedFileRule(),
        ];

        foreach ($rules as $rule) {
            $ctxNull = new ValidationContext('field');
            $this->assertTrue($rule->validate(null, $ctxNull), get_class($rule) . ' com null');
            $this->assertTrue($ctxNull->errorBag->isEmpty());

            $ctxEmpty = new ValidationContext('field');
            $this->assertTrue($rule->validate('', $ctxEmpty), get_class($rule) . ' com vazio');
            $this->assertTrue($ctxEmpty->errorBag->isEmpty());
        }
    }

    public function testNonScalarAndNonStringInputs(): void
    {
        $ctx = new ValidationContext('test');

        $this->assertFalse((new CpfRule())->validate(['array'], $ctx));
        $this->assertFalse((new CnpjRule())->validate(['array'], $ctx));
        $this->assertFalse((new PhoneRule())->validate(['array'], $ctx));
        $this->assertFalse((new FullNameRule())->validate(['array'], $ctx));
        $this->assertFalse((new EmailRule())->validate(123, $ctx));
        $this->assertFalse((new DateRule())->validate(['array'], $ctx));
        $this->assertFalse((new TimeRule())->validate(['array'], $ctx));
        $this->assertFalse((new DomainRule())->validate(123, $ctx));
        $this->assertFalse((new UrlRule())->validate(123, $ctx));
        $this->assertFalse((new UuidRule())->validate(123, $ctx));
        $this->assertFalse((new NoHtmlRule())->validate(123, $ctx));
        $this->assertFalse((new HtmlRule())->validate(123, $ctx));
        $this->assertFalse((new EmojisRule())->validate(['array'], $ctx));
    }

    public function testCustomErrorMessages(): void
    {
        $cases = [
            [new CpfRule(message: 'CPF Custom'), '123'],
            [new CnpjRule(message: 'CNPJ Custom'), '123'],
            [new PhoneRule(message: 'Phone Custom'), '123'],
            [new FullNameRule(message: 'Name Custom'), 'Unico'],
            [new EmailRule(message: 'Email Custom'), 'bad'],
            [new DateRule(message: 'Date Custom'), 'bad'],
            [new TimeRule(message: 'Time Custom'), 'bad'],
            [new DomainRule(message: 'Domain Custom'), 'bad'],
            [new UrlRule(message: 'Url Custom'), 'bad'],
            [new UuidRule(message: 'Uuid Custom'), 'bad'],
            [new NoHtmlRule(message: 'NoHtml Custom'), '<b>tag</b>'],
            [new HtmlRule(message: 'Html Custom'), 'no tag'],
            [new EmojisRule(allow: false, message: 'Emoji Custom'), '🎉'],
            [new EmojisRule(allow: true, only: true, message: 'Only Emoji Custom'), 'abc'],
        ];

        foreach ($cases as [$rule, $invalidValue]) {
            $ctx = new ValidationContext('field');
            $rule->validate($invalidValue, $ctx);
            $this->assertTrue($ctx->errorBag->has('field'), get_class($rule));
        }
    }

    public function testDateRuleWithDateTimeInterface(): void
    {
        $rule = new DateRule();
        $ctx = new ValidationContext('date');
        $this->assertTrue($rule->validate(new \DateTimeImmutable(), $ctx));
        $this->assertTrue($rule->validate(new \DateTime(), $ctx));
    }

    public function testUrlRuleWithCustomProtocols(): void
    {
        $rule = new UrlRule(protocols: ['ftp']);
        $ctx = new ValidationContext('url');

        $this->assertTrue($rule->validate('ftp://files.example.com', $ctx));
        $this->assertFalse($rule->validate('https://files.example.com', $ctx));
    }

    public function testHtmlRuleSanitize(): void
    {
        $rule = new HtmlRule(sanitize: true);
        $clean = $rule->sanitize('<p>Texto seguro</p><script>alert(1)</script>');
        $this->assertSame('<p>Texto seguro</p>alert(1)', $clean);

        // Sanitize não altera tipos não-string
        $this->assertSame(123, $rule->sanitize(123));
    }

    public function testInternationalPhone(): void
    {
        $this->assertTrue(PhoneRule::isValid('+14155552671', 'US'));
        $this->assertFalse(PhoneRule::isValid('invalid-phone', 'US'));
    }

    public function testUploadedFileRuleEdgeCases(): void
    {
        $rule = new UploadedFileRule(
            minSize: 100,
            maxSize: 500,
            extensions: ['txt'],
            mimeTypes: ['text/plain'],
            allowNonUploadedFiles: true,
        );

        $ctx = new ValidationContext('file');

        // Entrada não é arquivo
        $this->assertFalse($rule->validate('string', $ctx));
        $this->assertTrue($ctx->errorBag->has('file'));

        // Arquivo abaixo do tamanho mínimo
        $tmp = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmp, 'abc'); // 3 bytes
        $smallFile = new UploadedFile($tmp, 'small.txt', 3);
        $ctxSmall = new ValidationContext('file');
        $this->assertFalse($rule->validate($smallFile, $ctxSmall));
        $this->assertTrue($ctxSmall->errorBag->has('file'));
        unlink($tmp);
    }
}
