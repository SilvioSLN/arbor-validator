<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Validator\ArborValidator;
use Arbor\Validator\Attributes as V;

echo "=== Arbor Validator: 05 Nested DTOs and Lists (#[Nested], #[Each]) ===\n\n";

final readonly class ShippingAddressDTO
{
    public function __construct(
        #[V\Required]
        public string $street,

        #[V\Required]
        public string $city,

        #[V\Required, V\MinLength(5)]
        public string $postalCode,
    ) {
    }
}

final readonly class OrderItemDTO
{
    public function __construct(
        #[V\Required]
        public string $sku,

        #[V\Required, V\MinLength(1)]
        public int $quantity,

        #[V\Required]
        public float $price,
    ) {
    }
}

final readonly class PurchaseOrderDTO
{
    public function __construct(
        #[V\Required]
        public string $orderNumber,

        #[V\Required, V\Nested(ShippingAddressDTO::class)]
        public ShippingAddressDTO $shippingAddress,

        /** @var list<OrderItemDTO> */
        #[V\Required, V\Each(OrderItemDTO::class)]
        public array $items,
    ) {
    }
}

// 1. Valid nested order payload
$validOrderData = [
    'order_number'     => 'ORD-2026-9988',
    'shipping_address' => [
        'street'      => 'Avenida Paulista, 1000',
        'city'        => 'São Paulo',
        'postal_code' => '01310-100',
    ],
    'items' => [
        ['sku' => 'PHP-BOOK', 'quantity' => 2, 'price' => 79.90],
        ['sku' => 'LAPTOP-STAND', 'quantity' => 1, 'price' => 149.00],
    ],
];

$result = ArborValidator::validate(PurchaseOrderDTO::class, $validOrderData);

echo "1. Validating nested order with items list:\n";
echo "   Is Valid: " . ($result->isValid() ? 'YES' : 'NO') . "\n";

if ($result->isValid()) {
    /** @var PurchaseOrderDTO $order */
    $order = $result->data();
    echo "   Order: {$order->orderNumber}\n";
    echo "   Destination: {$order->shippingAddress->street}, {$order->shippingAddress->city}\n";
    echo "   Items count: " . count($order->items) . "\n";
    foreach ($order->items as $i => $item) {
        echo "     #{$i} SKU: {$item->sku}, Qty: {$item->quantity}, Unit: R$ {$item->price}\n";
    }
}

// 2. Demonstration of dotted error paths in nested structures
$invalidOrderData = [
    'order_number'     => 'ORD-FAIL',
    'shipping_address' => [
        'street'      => '', // Required error in address.street
        'city'        => 'São Paulo',
        'postal_code' => '12', // MinLength error in address.postal_code
    ],
    'items' => [
        ['sku' => '', 'quantity' => 'not-a-number', 'price' => 10.0],
    ],
];

$failResult = ArborValidator::validate(PurchaseOrderDTO::class, $invalidOrderData);

echo "\n2. Dotted error paths in nested structures:\n";
foreach ($failResult->errors() as $fieldPath => $messages) {
    echo "   - Path '{$fieldPath}': " . implode('; ', $messages) . "\n";
}

echo "\nCompleted successfully.\n";
