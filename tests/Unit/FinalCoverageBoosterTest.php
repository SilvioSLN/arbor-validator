<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Attributes as V;
use Arbor\Validator\Core\ClassMapper;
use Arbor\Validator\Core\Coercer;
use Arbor\Validator\Core\ErrorBag;
use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Core\ValidationResult;
use Arbor\Validator\Exceptions\ValidatorException;
use Arbor\Validator\Files\UploadedFile;
use Arbor\Validator\I18n\Translator;
use Arbor\Validator\Rules\CnpjRule;
use Arbor\Validator\Rules\CpfRule;
use Arbor\Validator\Rules\NoHtmlRule;
use Arbor\Validator\Rules\UploadedFileRule;
use Arbor\Validator\Rules\UrlRule;
use Arbor\Validator\Schemas\StringSchema;
use PHPUnit\Framework\TestCase;

final readonly class BoosterDTO
{
    /**
     * @param array<string, mixed>|null $avatarArray
     */
    public function __construct(
        #[V\Time(format: 'H:i')]
        public ?string $shiftTime = null,

        public ?string $nullableWithoutDefault = null,

        #[V\UploadedFile(allowNonUploadedFiles: true)]
        public ?UploadedFile $uploadedFileObj = null,

        #[V\UploadedFile(allowNonUploadedFiles: true)]
        public ?array $avatarArray = null,

        public string $role = 'guest',
    ) {
    }
}

final readonly class NamedPropertyAttrDTO
{
    #[V\Required]
    public string $fieldOnProperty;

    public function __construct(string $fieldOnProperty)
    {
        $this->fieldOnProperty = $fieldOnProperty;
    }
}

final readonly class SubItemDTO
{
    public function __construct(
        #[V\Required]
        public string $itemName,
    ) {
    }
}

final readonly class HtmlAndListDTO
{
    /**
     * @param list<SubItemDTO> $subItems
     */
    public function __construct(
        #[V\Html(sanitize: true)]
        public string $htmlContent,

        #[V\Each(SubItemDTO::class)]
        public array $subItems = [],
    ) {
    }
}

final readonly class ExplicitNestedDTO
{
    public function __construct(
        #[V\Nested(SubItemDTO::class)]
        public mixed $item,
    ) {
    }
}

final class FinalCoverageBoosterTest extends TestCase
{
    public function testClassMapperExtraBranches(): void
    {
        $mapper = new ClassMapper();
        $ctx = new ValidationContext();

        $tmp = tempnam(sys_get_temp_dir(), 'bst_');
        file_put_contents($tmp, 'data');
        $upFile = new UploadedFile($tmp, 'test.txt', 4);

        $payload = [
            'shiftTime' => '08:30',
            'uploadedFileObj' => $upFile,
            'avatarArray' => $upFile,
            'role' => '', // Vazio com default no construtor
            // nullableWithoutDefault omitido propositalmente
        ];

        /** @var BoosterDTO $dto */
        $dto = $mapper->validateAndMap(BoosterDTO::class, $payload, $ctx);

        $this->assertInstanceOf(BoosterDTO::class, $dto);
        $this->assertSame('08:30', $dto->shiftTime);
        $this->assertNull($dto->nullableWithoutDefault);
        $this->assertInstanceOf(UploadedFile::class, $dto->uploadedFileObj);
        $this->assertIsArray($dto->avatarArray);
        $this->assertSame('test.txt', $dto->avatarArray['name']);

        unlink($tmp);
    }

    public function testCoercerRemainingBranches(): void
    {
        // toBool com inteiros e variações em português
        $this->assertTrue(Coercer::toBool(1));
        $this->assertTrue(Coercer::toBool(42));
        $this->assertFalse(Coercer::toBool(0));
        $this->assertTrue(Coercer::toBool('s'));
        $this->assertTrue(Coercer::toBool('sim'));
        $this->assertFalse(Coercer::toBool('n'));
        $this->assertFalse(Coercer::toBool('nao'));
        $this->assertFalse(Coercer::toBool('não'));

        // toFloat e toInt com tipos já corretos e numéricos
        $this->assertSame(10.0, Coercer::toFloat(10));
        $this->assertSame(12.34, Coercer::toFloat('12.34'));
        $this->assertSame(4.5, Coercer::toFloat(4.5));
        $this->assertSame(42, Coercer::toInt(42));
        $this->assertSame(0, Coercer::toInt('0'));

        // toDateTimeImmutable e toDateTime com ISO sem formato
        $dti = Coercer::toDateTimeImmutable('2026-09-02 15:30:00');
        $this->assertInstanceOf(\DateTimeImmutable::class, $dti);
        $this->assertSame('2026-09-02 15:30:00', $dti->format('Y-m-d H:i:s'));

        $dt = Coercer::toDateTime('2026-09-02 15:30:00');
        $this->assertInstanceOf(\DateTime::class, $dt);
        $this->assertSame('2026-09-02 15:30:00', $dt->format('Y-m-d H:i:s'));

        // toDateTime com string inválida
        $this->assertSame('data_invalida_xyz', Coercer::toDateTime('data_invalida_xyz'));

        // Coerce com string vazia e nullable
        $this->assertNull(Coercer::coerce('', 'string', isNullable: true));
    }

    public function testUploadedFileRemainingBranches(): void
    {
        // Parse size com sufixo B e desconhecido
        $this->assertSame(256, UploadedFile::parseSizeToBytes('256B'));
        $this->assertSame(123, UploadedFile::parseSizeToBytes('123X'));

        // UploadedFile vazio
        $emptyFile = new UploadedFile('', 'test.txt', 0);
        $this->assertFalse($emptyFile->isValid());

        // Arquivo sem extensão
        $noExtFile = new UploadedFile(sys_get_temp_dir(), 'arquivo_sem_extensao', 10);
        $this->assertSame('', $noExtFile->getExtension());

        // Memoização do MIME type
        $tmpMime = tempnam(sys_get_temp_dir(), 'mime_');
        file_put_contents($tmpMime, 'test');
        $mimeFile = new UploadedFile($tmpMime, 'arq.txt', 4);
        $firstMime = $mimeFile->getRealMimeType();
        $secondMime = $mimeFile->getRealMimeType();
        $this->assertSame($firstMime, $secondMime);
        unlink($tmpMime);

        // moveTo com diretório impossível deve lançar ValidatorException
        $tmp = tempnam(sys_get_temp_dir(), 'mv_');
        file_put_contents($tmp, '123');
        $file = new UploadedFile($tmp, 'test.txt', 3);

        $this->expectException(ValidatorException::class);
        $file->moveTo('/proc/diretorio_impossivel_12345/arquivo.txt');
        unlink($tmp);
    }

    public function testValidationResultWithErrorBag(): void
    {
        $bag = new ErrorBag(['campo' => ['Mensagem de erro']]);
        $res = ValidationResult::failure($bag);

        $this->assertTrue($res->failed());
        $this->assertSame(['campo' => ['Mensagem de erro']], $res->errors());
    }

    public function testTranslatorFallbackToKey(): void
    {
        $t = Translator::getInstance();
        $this->assertSame('chave_inexistente_total', $t->get('chave_inexistente_total'));
    }

    public function testStringSchemaDefaultMessagesAndLowercase(): void
    {
        // lowercase
        $lower = (new StringSchema())->lowercase();
        $this->assertSame('silvio', $lower->parse('SILVIO'));

        // min e max com mensagens padrão
        $schema = (new StringSchema())->min(3)->max(5);
        $failMin = $schema->safeParse('ab');
        $this->assertTrue($failMin->failed());
        $this->assertSame('O campo :attribute deve conter no mínimo 3 caracteres.', $failMin->firstError());

        $failMax = $schema->safeParse('abcdef');
        $this->assertTrue($failMax->failed());
        $this->assertSame('O campo :attribute deve conter no máximo 5 caracteres.', $failMax->firstError());
    }

    public function testRulesSpecificMissingBranches(): void
    {
        // NoHtmlRule com tag img
        $noHtml = new NoHtmlRule();
        $this->assertFalse($noHtml->validate('<img src="x" onerror="evil">', new ValidationContext()));

        // UrlRule com esquema não permitido
        $this->assertFalse(UrlRule::isValid('javascript:alert(1)'));

        // CnpjRule com tamanho > 14 e com letras quando allowAlphanumeric é false
        $this->assertFalse(CnpjRule::isValid('123456789012345')); // 15 chars
        $this->assertFalse(CnpjRule::isValid('12.ABC.345/01DE-35', allowAlphanumeric: false));

        // UploadedFileRule com extensão e mime inválidos
        $tmp = tempnam(sys_get_temp_dir(), 'ufr_');
        file_put_contents($tmp, 'plain text');
        $file = new UploadedFile($tmp, 'documento.txt', 10);

        $ruleExt = new UploadedFileRule(extensions: ['png'], allowNonUploadedFiles: true);
        $this->assertFalse($ruleExt->validate($file, new ValidationContext()));

        $ruleMime = new UploadedFileRule(mimeTypes: ['image/jpeg'], allowNonUploadedFiles: true);
        $this->assertFalse($ruleMime->validate($file, new ValidationContext()));

        // Mensagens customizadas
        $ruleCustom = new UploadedFileRule(
            maxSize: 5,
            minSize: 1000,
            message: 'Erro geral de arquivo',
            allowNonUploadedFiles: true,
        );
        $ctxCustom = new ValidationContext('f');
        $ruleCustom->validate($file, $ctxCustom);
        $this->assertSame('Erro geral de arquivo', $ctxCustom->errorBag->first('f'));

        // UploadedFileRule minSize default message
        $ruleMin = new UploadedFileRule(minSize: 1000, allowNonUploadedFiles: true);
        $ctxMin = new ValidationContext('arq');
        $this->assertFalse($ruleMin->validate($file, $ctxMin));
        $this->assertSame('O arquivo arq é menor que o tamanho mínimo de 1000.', $ctxMin->errorBag->first('arq'));

        // NoHtmlRule com tag incompleta
        $this->assertFalse(NoHtmlRule::isValid('Texto com <script sem fechamento'));

        // CPF com DV1 correto mas DV2 incorreto
        $this->assertFalse(CpfRule::isValid('529.982.247-21'));

        // CNPJ com DV1 correto mas DV2 incorreto
        $this->assertFalse(CnpjRule::isValid('11.222.333/0001-80'));

        unlink($tmp);
    }

    public function testArborValidatorNonArrayForDtoAndSanitizers(): void
    {
        $res = \Arbor\Validator\ArborValidator::validate(BoosterDTO::class, 'string_invalida');
        $this->assertTrue($res->failed());
        $this->assertSame('Os dados para o DTO devem ser um array associativo.', $res->firstError());

        // Sanitizers com tipos não string
        $emailRule = new \Arbor\Validator\Rules\EmailRule();
        $this->assertSame(12345, $emailRule->sanitize(12345));

        // Coerce match direto
        $this->assertTrue(Coercer::coerce('1', 'bool'));
        $this->assertSame('str', Coercer::coerce('str', 'string'));
        $this->assertSame(['item'], Coercer::coerce('item', 'array'));
        $this->assertInstanceOf(\DateTime::class, Coercer::coerce('2026-09-02', \DateTime::class));
        $this->assertInstanceOf(\DateTimeImmutable::class, Coercer::coerce('2026-09-02', \DateTimeImmutable::class));
        $this->assertSame(10, Coercer::coerce('10', 'int'));
        $this->assertSame(10.5, Coercer::coerce('10.5', 'float'));

        // EmailRule branches
        $this->assertFalse(\Arbor\Validator\Rules\EmailRule::isValid('user@sem-ponto'));
        $this->assertFalse(\Arbor\Validator\Rules\EmailRule::isValid('user@@duplo.com'));
        $ctxEmail = new ValidationContext('email');
        $this->assertFalse($emailRule->validate('invalid_email', $ctxEmail));
        $this->assertTrue($ctxEmail->errorBag->has('email'));

        // UrlRule default message
        $urlRule = new UrlRule();
        $ctxUrl = new ValidationContext('url');
        $this->assertFalse($urlRule->validate('https://', $ctxUrl));
        $this->assertTrue($ctxUrl->errorBag->has('url'));

        // UrlRule com tipo não-string
        $ctxUrlType = new ValidationContext('url');
        $this->assertFalse((new UrlRule())->validate(12345, $ctxUrlType));
        $this->assertTrue($ctxUrlType->errorBag->has('url'));

        // CNPJ com letras nos dígitos verificadores
        $this->assertFalse(CnpjRule::isValid('12ABC34501DEAB'));

        // UrlRule com mensagem customizada
        $urlCustom = new UrlRule(message: 'URL Inválida Custom');
        $ctxUrlCustom = new ValidationContext('url');
        $this->assertFalse($urlCustom->validate('bad_url', $ctxUrlCustom));
        $this->assertSame('URL Inválida Custom', $ctxUrlCustom->errorBag->first('url'));

        // NoHtmlRule default message
        $noHtmlRule = new NoHtmlRule();
        $ctxNoHtml = new ValidationContext('html');
        $this->assertFalse($noHtmlRule->validate('<b>tag</b>', $ctxNoHtml));
        $this->assertSame('O campo html não pode conter tags HTML ou scripts.', $ctxNoHtml->errorBag->first('html'));
    }

    public function testPropertyLevelAttributesMapping(): void
    {
        $mapper = new ClassMapper();
        $ctx = new ValidationContext();
        $dto = $mapper->validateAndMap(NamedPropertyAttrDTO::class, ['fieldOnProperty' => 'ok'], $ctx);

        $this->assertNotNull($dto);
        $this->assertTrue($ctx->errorBag->isEmpty());
        $this->assertSame('ok', $dto->fieldOnProperty);

        // Testa Html Attribute sanitize e Each com DTO
        $ctx2 = new ValidationContext();
        /** @var HtmlAndListDTO $listDto */
        $listDto = $mapper->validateAndMap(HtmlAndListDTO::class, [
            'htmlContent' => '<p>Texto</p><script>alert(1)</script>',
            'subItems' => [
                ['itemName' => 'Item 1'],
                ['itemName' => 'Item 2'],
            ],
        ], $ctx2);

        $this->assertInstanceOf(HtmlAndListDTO::class, $listDto);
        $this->assertSame('<p>Texto</p>alert(1)', $listDto->htmlContent);
        $this->assertCount(2, $listDto->subItems);
        $this->assertInstanceOf(SubItemDTO::class, $listDto->subItems[0]);
        $this->assertSame('Item 1', $listDto->subItems[0]->itemName);
    }

    public function testExplicitNestedAttribute(): void
    {
        $mapper = new ClassMapper();
        $ctx = new ValidationContext();

        /** @var ExplicitNestedDTO $dto */
        $dto = $mapper->validateAndMap(ExplicitNestedDTO::class, [
            'item' => ['itemName' => 'Item Aninhado'],
        ], $ctx);

        $this->assertInstanceOf(ExplicitNestedDTO::class, $dto);
        $this->assertInstanceOf(SubItemDTO::class, $dto->item);
        $this->assertSame('Item Aninhado', $dto->item->itemName);
    }
}
