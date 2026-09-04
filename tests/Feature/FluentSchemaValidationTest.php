<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Feature;

use Arbor\Validator\AV;
use Arbor\Validator\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class FluentSchemaValidationTest extends TestCase
{
    public function testCompleteFluentSchemaValidation(): void
    {
        $schema = AV::shape([
            'name' => AV::string()->fullName()->min(3)->max(100),
            'email' => AV::string()->email()->transform(fn($e) => strtolower(trim($e))),
            'password' => AV::string()->min(8),
            'password_confirmation' => AV::string(),
            'cpf' => AV::string()->cpf(stripMask: true),
            'cnpj' => AV::string()->cnpj(allowAlphanumeric: true, stripMask: true)->optional(),
            'phone' => AV::string()->phone('BR', stripMask: true),
            'date' => AV::string()->date('Y-m-d')->coerceDate(),
        ])->sameAs('password_confirmation', 'password', 'A confirmação de senha não coincide');

        $input = [
            'name' => 'Silvio Silva',
            'email' => '  USER@example.COM ',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'cpf' => '111.444.777-35',
            'cnpj' => '12.ABC.345/01DE-35',
            'phone' => '(11) 98765-4321',
            'date' => '2026-09-02',
        ];

        $result = $schema->safeParse($input);
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->failed());

        $data = $result->data();
        $this->assertSame('Silvio Silva', $data['name']);
        $this->assertSame('user@example.com', $data['email']);
        $this->assertSame('11144477735', $data['cpf']);
        $this->assertSame('12ABC34501DE35', $data['cnpj']);
        $this->assertSame('11987654321', $data['phone']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $data['date']);
        $this->assertSame('2026-09-02', $data['date']->format('Y-m-d'));
    }

    public function testPasswordMismatchProducesError(): void
    {
        $schema = AV::shape([
            'password' => AV::string()->min(8),
            'password_confirmation' => AV::string(),
        ])->sameAs('password_confirmation', 'password', 'A confirmação de senha não coincide');

        $result = $schema->safeParse([
            'password' => 'secret1234',
            'password_confirmation' => 'different_password',
        ]);

        $this->assertTrue($result->failed());
        $this->assertTrue($result->hasError('password_confirmation'));
        $this->assertSame('A confirmação de senha não coincide', $result->error('password_confirmation'));

        // Chamar data() em resultado com falha deve lançar ValidationException
        $this->expectException(ValidationException::class);
        $result->data();
    }

    public function testParseThrowsValidationException(): void
    {
        $schema = AV::shape([
            'email' => AV::string()->email(),
        ]);

        $this->expectException(ValidationException::class);
        $schema->parse(['email' => 'not-an-email']);
    }

    public function testPreprocessAndCatch(): void
    {
        // preprocess: apara espaços antes da validação
        $schema = AV::preprocess(
            fn($val) => is_string($val) ? trim($val) : $val,
            AV::string()->min(3)
        );

        $result = $schema->safeParse('  foo  ');
        $this->assertTrue($result->isValid());
        $this->assertSame('foo', $result->data());

        // catch: usa valor seguro caso a validação falhe
        $schemaWithCatch = AV::number()->min(1)->max(100)->catch(10);
        $resCatch = $schemaWithCatch->safeParse(999);
        $this->assertTrue($resCatch->isValid());
        $this->assertSame(10, $resCatch->data());
    }

    public function testSchemaManipulationPickOmitExtendPartial(): void
    {
        $base = AV::shape([
            'name' => AV::string(),
            'email' => AV::string()->email(),
        ]);

        // pick
        $picked = $base->pick(['email']);
        $this->assertArrayHasKey('email', $picked->getFields());
        $this->assertArrayNotHasKey('name', $picked->getFields());

        // omit
        $omitted = $base->omit(['name']);
        $this->assertArrayHasKey('email', $omitted->getFields());
        $this->assertArrayNotHasKey('name', $omitted->getFields());

        // extend
        $extended = $base->extend(['role' => AV::enum(['admin', 'user'])]);
        $this->assertArrayHasKey('role', $extended->getFields());

        // partial
        $partial = $base->partial();
        $this->assertTrue($partial->getFields()['name']->isOptional());
        $this->assertTrue($partial->getFields()['email']->isOptional());
    }
}
