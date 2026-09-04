<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\ArborValidator;
use Arbor\Validator\AV;
use Arbor\Validator\Exceptions\ValidationException;
use Arbor\Validator\I18n\Translator;
use PHPUnit\Framework\TestCase;

final readonly class SimpleFacadeDTO
{
    public function __construct(public string $title)
    {
    }
}

final class ArborValidatorFacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        Translator::reset();
    }

    public function testValidateWithDifferentTargets(): void
    {
        // 1. Target como classe DTO
        $resDto = ArborValidator::validate(SimpleFacadeDTO::class, ['title' => 'Titulo']);
        $this->assertTrue($resDto->isValid());
        $this->assertSame('Titulo', $resDto->data()->title);

        // 2. Target como Schema
        $resSchema = ArborValidator::validate(AV::string(), 'Texto');
        $this->assertTrue($resSchema->isValid());
        $this->assertSame('Texto', $resSchema->data());

        // 3. Target como array associativo de Schemas
        $resArray = ArborValidator::validate(['campo' => AV::string()], ['campo' => 'Valor']);
        $this->assertTrue($resArray->isValid());
        $this->assertSame('Valor', $resArray->data()['campo']);
    }

    public function testParseSuccessAndFailure(): void
    {
        // Sucesso
        $data = ArborValidator::parse(['nome' => AV::string()], ['nome' => 'Silvio']);
        $this->assertSame('Silvio', $data['nome']);

        // Falha lança ValidationException
        $this->expectException(ValidationException::class);
        ArborValidator::parse(['nome' => AV::string()], []);
    }

    public function testSetLocaleAndAddMessages(): void
    {
        ArborValidator::setLocale('en');
        $this->assertSame('en', Translator::getInstance()->getLocale());

        ArborValidator::addMessages('pt-BR', [
            'required' => 'Customizado: :attribute ausente.',
        ]);

        ArborValidator::setLocale('pt-BR');
        $msg = Translator::getInstance()->get('required', ['attribute' => 'nome']);
        $this->assertSame('Customizado: nome ausente.', $msg);
    }
}
