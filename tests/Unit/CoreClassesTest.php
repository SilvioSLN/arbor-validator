<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\Coercer;
use Arbor\Validator\Core\ErrorBag;
use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Core\ValidationResult;
use Arbor\Validator\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class CoreClassesTest extends TestCase
{
    public function testErrorBagCompleteMethods(): void
    {
        $bag = new ErrorBag();
        $this->assertTrue($bag->isEmpty());
        $this->assertFalse($bag->isNotEmpty());
        $this->assertSame(0, count($bag));
        $this->assertNull($bag->first());
        $this->assertSame([], $bag->get('inexistente'));

        $bag->add('email', 'Email inválido');
        $bag->add('email', 'Email obrigatório');
        $bag->add('senha', 'Senha curta');

        $this->assertFalse($bag->isEmpty());
        $this->assertTrue($bag->isNotEmpty());
        $this->assertSame(3, count($bag));
        $this->assertTrue($bag->has('email'));
        $this->assertFalse($bag->has('outro'));
        $this->assertSame('Email inválido', $bag->first());
        $this->assertSame('Senha curta', $bag->first('senha'));
        $this->assertNull($bag->first('inexistente'));
        $this->assertSame(['Email inválido', 'Email obrigatório'], $bag->get('email'));

        // Merge sem prefixo
        $otherBag = new ErrorBag(['nome' => ['Nome obrigatório']]);
        $bag->merge($otherBag);
        $this->assertTrue($bag->has('nome'));

        // Merge com prefixo
        $prefixedBag = new ErrorBag(['rua' => ['Rua inválida']]);
        $bag->merge($prefixedBag, 'endereco');
        $this->assertTrue($bag->has('endereco.rua'));
    }

    public function testValidationContextMethods(): void
    {
        ValidationContext::setTestingMode(true);
        $this->assertTrue(ValidationContext::isTestingMode());

        $root = [
            'simples' => 'valor1',
            'usuario' => [
                'perfil' => [
                    'nome' => 'Silvio',
                ],
            ],
        ];

        $context = new ValidationContext(path: 'item', rootData: $root);
        $this->assertSame('valor1', $context->getRootValue('simples'));
        $this->assertSame('Silvio', $context->getRootValue('usuario.perfil.nome'));
        $this->assertNull($context->getRootValue('usuario.inexistente'));
        $this->assertNull($context->getRootValue('nao.existe.nada'));

        // Se rootData não for array
        $scalarContext = new ValidationContext(path: '', rootData: 'texto');
        $this->assertNull($scalarContext->getRootValue('qualquer'));

        // Adição de erro com customPath
        $context->addError('Erro customizado', 'outro.campo');
        $this->assertTrue($context->errorBag->has('outro.campo'));

        $context->addErrorByKey('required', [], 'campo.obrigatorio');
        $this->assertTrue($context->errorBag->has('campo.obrigatorio'));
    }

    public function testValidationResultMethods(): void
    {
        $successResult = ValidationResult::success(['ok' => true]);
        $this->assertTrue($successResult->isValid());
        $this->assertFalse($successResult->failed());
        $this->assertSame(['ok' => true], $successResult->data());
        $this->assertSame(['ok' => true], $successResult->safeData());
        $this->assertNull($successResult->firstError());
        $this->assertNull($successResult->error('qualquer'));
        $this->assertSame([], $successResult->fieldErrors('qualquer'));
        $this->assertFalse($successResult->hasError('qualquer'));

        $failResult = ValidationResult::failure([
            'nome' => ['Nome inválido', 'Nome curto'],
        ], ['nome' => '']);

        $this->assertFalse($failResult->isValid());
        $this->assertTrue($failResult->failed());
        $this->assertSame(['nome' => ''], $failResult->safeData());
        $this->assertSame('Nome inválido', $failResult->firstError());
        $this->assertSame('Nome inválido', $failResult->error('nome'));
        $this->assertSame(['Nome inválido', 'Nome curto'], $failResult->fieldErrors('nome'));
        $this->assertTrue($failResult->hasError('nome'));

        $this->expectException(ValidationException::class);
        $failResult->data();
    }

    public function testValidationExceptionMethods(): void
    {
        $errors = [
            'email' => ['E-mail inválido'],
            'senha' => ['Senha curta'],
        ];

        $exception = new ValidationException($errors);
        $this->assertSame($errors, $exception->errors());
        $this->assertSame('E-mail inválido', $exception->firstError());
        $this->assertSame('E-mail inválido', $exception->error('email'));
        $this->assertSame('Senha curta', $exception->error('senha'));
        $this->assertNull($exception->error('inexistente'));
        $this->assertStringContainsString('E-mail inválido', $exception->getMessage());

        // Exception sem erros
        $emptyEx = new ValidationException([]);
        $this->assertNull($emptyEx->firstError());
    }

    public function testCoercerEdgeCases(): void
    {
        $this->assertSame(0, Coercer::toInt('0'));
        $this->assertSame('abc', Coercer::toInt('abc')); // Não numérico mantido
        $this->assertSame('abc', Coercer::toFloat('abc'));

        // toArray
        $this->assertSame([], Coercer::toArray(null));
        $this->assertSame([], Coercer::toArray(''));
        $this->assertSame(['item'], Coercer::toArray('item'));
        $this->assertSame(['a', 'b'], Coercer::toArray(['a', 'b']));

        // toString
        $stringable = new class {
            public function __toString(): string
            {
                return 'str_obj';
            }
        };
        $this->assertSame('str_obj', Coercer::toString($stringable));
        $this->assertSame('123', Coercer::toString(123));
        $this->assertSame(['array'], Coercer::toString(['array'])); // Não stringable mantido

        // toDateTime e toDateTimeImmutable
        $dt = new \DateTime('2026-09-02 12:00:00');
        $this->assertInstanceOf(\DateTimeImmutable::class, Coercer::toDateTimeImmutable($dt));
        $this->assertSame($dt, Coercer::toDateTime($dt));

        $dti = new \DateTimeImmutable('2026-09-02 12:00:00');
        $this->assertSame($dti, Coercer::toDateTimeImmutable($dti));
        $this->assertInstanceOf(\DateTime::class, Coercer::toDateTime($dti));

        $this->assertSame('data-invalida', Coercer::toDateTimeImmutable('data-invalida'));
        $this->assertSame(null, Coercer::toDateTimeImmutable(null));

        // coerce com tipo desconhecido
        $this->assertSame('qualquer', Coercer::coerce('qualquer', 'tipo_desconhecido'));
        $this->assertSame('qualquer', Coercer::coerce('qualquer', null));
    }
}
