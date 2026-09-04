<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Feature;

use Arbor\Validator\AV;
use Arbor\Validator\Exceptions\ValidationException;
use Arbor\Validator\Integration\ValidatesRequestTrait;
use PHPUnit\Framework\TestCase;

// Classe simulando o objeto Request do Arbor Router
class FakeArborRequest
{
    use ValidatesRequestTrait;

    /**
     * @param array<string, mixed> $inputs
     */
    public function __construct(private array $inputs = [])
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function inputs(): array
    {
        return $this->inputs;
    }
}

final class ArborRouterIntegrationTest extends TestCase
{
    public function testRequestValidateWithSchema(): void
    {
        $request = new FakeArborRequest([
            'name' => 'Silvio Silva',
            'email' => 'silvio@example.com',
        ]);

        $result = $request->validate([
            'name' => AV::string()->fullName(),
            'email' => AV::string()->email(),
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame('Silvio Silva', $result->data()['name']);
    }

    public function testRequestValidateOrFailThrowsException(): void
    {
        $request = new FakeArborRequest([
            'email' => 'invalid-email',
        ]);

        $this->expectException(ValidationException::class);
        $request->validateOrFail([
            'email' => AV::string()->email(),
        ]);
    }
}
