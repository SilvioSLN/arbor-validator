<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Feature;

use Arbor\Validator\ArborValidator;
use Arbor\Validator\Tests\Fixtures\AddressDTO;
use Arbor\Validator\Tests\Fixtures\OrderDTO;
use Arbor\Validator\Tests\Fixtures\OrderItemDTO;
use PHPUnit\Framework\TestCase;

final class NestedDtoAndArrayValidationTest extends TestCase
{
    public function testValidNestedDtoAndItemLists(): void
    {
        $payload = [
            'customer' => 'Empresa Teste Ltda',
            'address' => [
                'street' => 'Av. Paulista, 1000',
                'city' => 'São Paulo',
                'zip' => '01310-100',
            ],
            'items' => [
                [
                    'product' => 'Teclado Mecânico',
                    'quantity' => '2', // Testando coerção inteligente de string para int
                    'price' => '250.50', // Coerção de string para float
                ],
                [
                    'product' => 'Mouse Gamer',
                    'quantity' => 1,
                    'price' => 120.00,
                ],
            ],
        ];

        $result = ArborValidator::validate(OrderDTO::class, $payload);

        $this->assertTrue($result->isValid());
        /** @var OrderDTO $order */
        $order = $result->data();

        $this->assertInstanceOf(OrderDTO::class, $order);
        $this->assertInstanceOf(AddressDTO::class, $order->address);
        $this->assertSame('Av. Paulista, 1000', $order->address->street);
        $this->assertCount(2, $order->items);
        $this->assertInstanceOf(OrderItemDTO::class, $order->items[0]);
        $this->assertSame(2, $order->items[0]->quantity);
        $this->assertSame(250.50, $order->items[0]->price);
    }

    public function testNestedErrorsUseDotNotation(): void
    {
        $payload = [
            'customer' => 'Cliente',
            'address' => [
                'street' => '', // Obrigatório vazio
                'city' => 'Curitiba',
            ],
            'items' => [
                [
                    'product' => 'Produto A',
                    'quantity' => '', // Obrigatório vazio
                    'price' => 10.0,
                ],
            ],
        ];

        $result = ArborValidator::validate(OrderDTO::class, $payload);

        $this->assertTrue($result->failed());
        $this->assertTrue($result->hasError('address.street'));
        $this->assertTrue($result->hasError('items.0.quantity'));
    }
}
