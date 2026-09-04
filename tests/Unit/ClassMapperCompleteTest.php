<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Attributes as V;
use Arbor\Validator\Core\ClassMapper;
use Arbor\Validator\Core\ValidationContext;
use PHPUnit\Framework\TestCase;

class EmptyConstructorDTO
{
}

final readonly class SnakeCaseDTO
{
    public function __construct(
        #[V\Required]
        public string $userFirstName,

        #[V\Required]
        public string $userLastName,
    ) {
    }
}

final readonly class TransformDTO
{
    public function __construct(
        #[V\Transform('strtoupper')]
        public string $code,
    ) {
    }
}

final readonly class ThrowingDTO
{
    public function __construct(public string $val)
    {
        throw new \RuntimeException('Falha interna no construtor');
    }
}

final class ClassMapperCompleteTest extends TestCase
{
    private ClassMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ClassMapper();
    }

    public function testNonExistentDtoClass(): void
    {
        $context = new ValidationContext();
        $res = $this->mapper->validateAndMap('ClasseInexistenteXYZ', [], $context); // @phpstan-ignore argument.type

        $this->assertNull($res);
        $this->assertTrue($context->errorBag->isNotEmpty());
        $this->assertStringContainsString('Classe DTO não encontrada', $context->errorBag->first());
    }

    public function testDtoWithoutConstructor(): void
    {
        $context = new ValidationContext();
        $dto = $this->mapper->validateAndMap(EmptyConstructorDTO::class, [], $context);

        $this->assertInstanceOf(EmptyConstructorDTO::class, $dto);
        $this->assertTrue($context->errorBag->isEmpty());
    }

    public function testSnakeCasePayloadMapping(): void
    {
        $context = new ValidationContext();
        $payload = [
            'user_first_name' => 'Silvio',
            'user_last_name' => 'Silva',
        ];

        /** @var SnakeCaseDTO $dto */
        $dto = $this->mapper->validateAndMap(SnakeCaseDTO::class, $payload, $context);

        $this->assertInstanceOf(SnakeCaseDTO::class, $dto);
        $this->assertSame('Silvio', $dto->userFirstName);
        $this->assertSame('Silva', $dto->userLastName);
    }

    public function testTransformAttributeOnDto(): void
    {
        $context = new ValidationContext();
        /** @var TransformDTO $dto */
        $dto = $this->mapper->validateAndMap(TransformDTO::class, ['code' => 'abc'], $context);

        $this->assertInstanceOf(TransformDTO::class, $dto);
        $this->assertSame('ABC', $dto->code);
    }

    public function testConstructorExceptionHandled(): void
    {
        $context = new ValidationContext();
        $dto = $this->mapper->validateAndMap(ThrowingDTO::class, ['val' => 'ok'], $context);

        $this->assertNull($dto);
        $this->assertTrue($context->errorBag->isNotEmpty());
        $this->assertStringContainsString('Erro ao instanciar DTO', $context->errorBag->first());
    }
}
