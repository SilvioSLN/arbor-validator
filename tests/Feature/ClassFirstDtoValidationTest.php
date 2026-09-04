<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Feature;

use Arbor\Validator\ArborValidator;
use Arbor\Validator\Tests\Fixtures\RegisterUserDTO;
use PHPUnit\Framework\TestCase;

final class ClassFirstDtoValidationTest extends TestCase
{
    private ?string $tempImage = null;

    protected function setUp(): void
    {
        ArborValidator::setTestingMode(true);
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $this->tempImage = sys_get_temp_dir() . '/test_avatar_' . uniqid() . '.png';
        file_put_contents($this->tempImage, $pngData);
    }

    protected function tearDown(): void
    {
        if ($this->tempImage !== null && file_exists($this->tempImage)) {
            unlink($this->tempImage);
        }
    }

    public function testValidDtoInstantiation(): void
    {
        $payload = [
            'name' => 'Silvio Silva',
            'email' => 'silvio@example.com',
            'password' => 'secret1234',
            'passwordConfirmation' => 'secret1234',
            'cpf' => '111.444.777-35',
            'cnpj' => '12.ABC.345/01DE-35',
            'phone' => '(11) 98765-4321',
            'birthDate' => '1995-05-15',
            'preferredContactTime' => '14:30',
            'websiteDomain' => 'meusite.com.br',
            'profileUrl' => 'https://meusite.com.br/perfil',
            'affiliateUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'bio' => 'Desenvolvedor PHP e apaixonado por open source.',
            'avatar' => [
                'tmp_name' => $this->tempImage,
                'name' => 'avatar.png',
                'size' => 1024,
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $result = ArborValidator::validate(RegisterUserDTO::class, $payload);

        $this->assertTrue($result->isValid());
        $this->assertFalse($result->failed());

        /** @var RegisterUserDTO $dto */
        $dto = $result->data();

        $this->assertInstanceOf(RegisterUserDTO::class, $dto);
        $this->assertSame('Silvio Silva', $dto->name);
        $this->assertSame('silvio@example.com', $dto->email);
        $this->assertSame('secret1234', $dto->password);
        $this->assertSame('111.444.777-35', $dto->cpf);
        $this->assertSame('12.ABC.345/01DE-35', $dto->cnpj);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->birthDate);
        $this->assertSame('1995-05-15', $dto->birthDate->format('Y-m-d'));
        $this->assertSame('14:30', $dto->preferredContactTime);
        $this->assertSame('meusite.com.br', $dto->websiteDomain);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $dto->affiliateUuid);
    }

    public function testDtoValidationFailsWithErrors(): void
    {
        $payload = [
            'name' => 'Silvio', // Apenas 1 palavra (deve falhar em FullName)
            'email' => 'invalid-email', // E-mail inválido
            'password' => '123', // Menor que 8
            'passwordConfirmation' => '456', // Não coincide
            'cpf' => '000.000.000-00', // Sequência repetida
            'phone' => '123', // Telefone inválido
        ];

        $result = ArborValidator::validate(RegisterUserDTO::class, $payload);

        $this->assertTrue($result->failed());
        $this->assertTrue($result->hasError('name'));
        $this->assertTrue($result->hasError('email'));
        $this->assertTrue($result->hasError('password'));
        $this->assertTrue($result->hasError('passwordConfirmation'));
        $this->assertTrue($result->hasError('cpf'));
        $this->assertTrue($result->hasError('phone'));

        $this->assertSame('A confirmação de senha não confere', $result->error('passwordConfirmation'));
    }
}
