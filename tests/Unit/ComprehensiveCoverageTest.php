<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Attributes as V;
use Arbor\Validator\Core\ClassMapper;
use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Core\ValidationResult;
use Arbor\Validator\Files\UploadedFile;
use Arbor\Validator\I18n\Translator;
use Arbor\Validator\Rules\CnpjRule;
use Arbor\Validator\Rules\CpfRule;
use Arbor\Validator\Rules\EmailRule;
use Arbor\Validator\Rules\NoHtmlRule;
use Arbor\Validator\Rules\SameAsRule;
use Arbor\Validator\Rules\UploadedFileRule;
use Arbor\Validator\Rules\UrlRule;
use Arbor\Validator\Schemas\EnumSchema;
use Arbor\Validator\Schemas\StringSchema;
use PHPUnit\Framework\TestCase;

final readonly class CoverageExtraDTO
{
    /**
     * @param array<string, mixed> $requiredMissingFile
     * @param list<mixed> $numbers
     */
    public function __construct(
        #[V\Optional]
        public ?string $unassignedField,

        #[V\Required, V\UploadedFile(extensions: ['png'], allowNonUploadedFiles: true)]
        public UploadedFile $uploadedObject,

        #[V\Required, V\UploadedFile]
        public array $requiredMissingFile,

        #[V\Each('int')]
        public array $numbers = [],

        #[V\DateTime(format: 'd/m/Y H:i:s')]
        public ?\DateTimeImmutable $eventAt = null,

        #[V\Coerce(dateFormat: 'd/m/Y')]
        public ?\DateTimeImmutable $hiredAt = null,
    ) {
    }
}

final class ComprehensiveCoverageTest extends TestCase
{
    public function testAttributesDirectValidation(): void
    {
        $ctx = new ValidationContext();

        // MaxLength
        $maxAttr = new V\MaxLength(5, 'Max custom');
        $this->assertTrue($maxAttr->validate(null, $ctx));
        $this->assertTrue($maxAttr->validate('', $ctx));
        $this->assertTrue($maxAttr->validate('12345', $ctx));
        $this->assertFalse($maxAttr->validate('123456', $ctx));

        $maxDefaultAttr = new V\MaxLength(3);
        $this->assertFalse($maxDefaultAttr->validate('1234', $ctx));

        // MinLength
        $minAttr = new V\MinLength(5, 'Min custom');
        $this->assertTrue($minAttr->validate(null, $ctx));
        $this->assertFalse($minAttr->validate('123', $ctx));

        // Required
        $reqAttr = new V\Required('Req custom');
        $this->assertTrue($reqAttr->validate('valor', $ctx));
        $this->assertFalse($reqAttr->validate(null, $ctx));
        $this->assertFalse($reqAttr->validate('', $ctx));

        // Instâncias simples
        $this->assertInstanceOf(V\Nullable::class, new V\Nullable());
        $this->assertInstanceOf(V\Optional::class, new V\Optional());
        $coerce = new V\Coerce('int', 'Y-m-d');
        $this->assertSame('int', $coerce->type);
        $this->assertSame('Y-m-d', $coerce->dateFormat);
    }

    public function testClassMapperAdvancedBranches(): void
    {
        $mapper = new ClassMapper();
        $ctx = new ValidationContext();

        $tmpFile = tempnam(sys_get_temp_dir(), 'cov_');
        file_put_contents($tmpFile, 'png_bytes');
        $upFile = new UploadedFile($tmpFile, 'image.png', 9);

        $payload = [
            'uploadedObject' => $upFile,
            'numbers' => ['1', '2', '3'],
            'eventAt' => '02/09/2026 14:00:00',
            'hiredAt' => '15/05/2020',
        ];

        /** @var CoverageExtraDTO|null $dto */
        $dto = $mapper->validateAndMap(CoverageExtraDTO::class, $payload, $ctx);

        $this->assertNull($dto); // Falha pois requiredMissingFile não foi enviado
        $this->assertTrue($ctx->errorBag->has('requiredMissingFile'));

        unlink($tmpFile);
    }

    public function testRulesMissingBranches(): void
    {
        // EmailRule formato inválido sem domínio com ponto
        $emailRule = new EmailRule();
        $this->assertTrue($emailRule->validate('test@example.com', new ValidationContext()));
        $this->assertFalse($emailRule->validate('user@localhost', new ValidationContext()));

        // NoHtmlRule com tag maliciosa
        $noHtml = new NoHtmlRule();
        $this->assertFalse($noHtml->validate('Texto com <a href="evil">link</a>', new ValidationContext()));

        // SameAsRule
        $sameAs = new SameAsRule('origem');
        $ctxSame = new ValidationContext(rootData: ['origem' => 'abc']);
        $this->assertTrue($sameAs->validate('abc', $ctxSame));
        $this->assertFalse($sameAs->validate('outro', $ctxSame));

        // CnpjRule e CpfRule tamanhos inválidos
        $this->assertFalse(CnpjRule::isValid('123')); // menor que 14
        $this->assertFalse(CpfRule::isValid('123'));  // menor que 11

        // UrlRule com esquema inexistente
        $this->assertFalse(UrlRule::isValid('sem-esquema'));

        // UploadedFileRule extensão e mime inválidos
        $tmp = tempnam(sys_get_temp_dir(), 'up_');
        file_put_contents($tmp, 'conteudo');
        $file = new UploadedFile($tmp, 'arq.exe', 8);

        $rule = new UploadedFileRule(
            extensions: ['jpg'],
            mimeTypes: ['image/jpeg'],
            allowNonUploadedFiles: true,
        );
        $ctxUp = new ValidationContext('up');
        $this->assertFalse($rule->validate($file, $ctxUp));
        $this->assertTrue($ctxUp->errorBag->has('up'));
        unlink($tmp);
    }

    public function testTranslatorParamsInterpolation(): void
    {
        $translator = Translator::getInstance();

        $msg = $translator->get('datetime', [
            'attribute' => 'data',
            'format' => new \DateTime('2026-09-02 10:00:00'),
        ]);
        $this->assertStringContainsString('2026-09-02 10:00:00', $msg);

        $msgBool = $translator->get('required', [
            'attribute' => true,
        ]);
        $this->assertStringContainsString('true', $msgBool);
    }

    public function testValidationResultEmptyErrors(): void
    {
        $res = new ValidationResult(true, 'dados', []);
        $this->assertNull($res->firstError());
    }

    public function testStringSchemaCustomMessages(): void
    {
        $schema = (new StringSchema())
            ->trim()
            ->min(5, 'Muito curto')
            ->max(10, 'Muito longo');

        $this->assertSame('Texto', $schema->parse('  Texto  '));

        $failMin = $schema->safeParse('abc');
        $this->assertTrue($failMin->failed());
        $this->assertSame('Muito curto', $failMin->firstError());

        $failMax = $schema->safeParse('1234567890123');
        $this->assertTrue($failMax->failed());
        $this->assertSame('Muito longo', $failMax->firstError());
    }

    public function testEnumSchemaInvalidCasesConstructor(): void
    {
        // Construtor com string que não é enum class
        $schema = new EnumSchema('ClasseNaoEnum'); // @phpstan-ignore argument.type
        $this->assertTrue($schema->safeParse('qualquer')->failed());
    }
}
