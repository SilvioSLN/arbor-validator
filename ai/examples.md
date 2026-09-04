# Real-World Examples: Arbor Validator

This document provides complete, copy-pasteable implementation examples for common enterprise scenarios.

---

## Example 1: E-Commerce Checkout Payload (Nested DTOs + Lists)

```php
namespace App\DTOs;

use Arbor\Validator\Attributes as V;

final readonly class CustomerDTO
{
    public function __construct(
        #[V\Required, V\FullName]
        public string $name,

        #[V\Required, V\Email]
        public string $email,

        #[V\Required, V\Cpf(stripMask: true)]
        public string $cpf,

        #[V\Required, V\Phone(country: 'BR', stripMask: true)]
        public string $phone,
    ) {}
}

final readonly class ShippingAddressDTO
{
    public function __construct(
        #[V\Required] public string $street,
        #[V\Required] public string $number,
        #[V\Optional] public ?string $complement = null,
        #[V\Required] public string $district,
        #[V\Required] public string $city,
        #[V\Required, V\MinLength(2), V\MaxLength(2)] public string $state,
        #[V\Required, V\MinLength(8)] public string $postalCode,
    ) {}
}

final readonly class CartItemDTO
{
    public function __construct(
        #[V\Required] public string $sku,
        #[V\Required, V\MinLength(1)] public int $quantity,
        #[V\Required] public float $unitPrice,
    ) {}
}

final readonly class CheckoutOrderDTO
{
    public function __construct(
        #[V\Required, V\Nested(CustomerDTO::class)]
        public CustomerDTO $customer,

        #[V\Required, V\Nested(ShippingAddressDTO::class)]
        public ShippingAddressDTO $shippingAddress,

        /** @var list<CartItemDTO> */
        #[V\Required, V\Each(CartItemDTO::class)]
        public array $items,

        #[V\Optional]
        public ?string $couponCode = null,
    ) {}
}

// Controller Execution:
use Arbor\Validator\ArborValidator;

$result = ArborValidator::validate(CheckoutOrderDTO::class, $requestPayload);

if ($result->failed()) {
    return Response::json(['errors' => $result->errors()], 422);
}

/** @var CheckoutOrderDTO $order */
$order = $result->data();
```

---

## Example 2: B2B Company Registration (RFB 2024 CNPJ & Phone)

```php
namespace App\DTOs;

use Arbor\Validator\Attributes as V;

final readonly class CompanyRegistrationDTO
{
    public function __construct(
        #[V\Required]
        public string $companyName,

        #[V\Required]
        public string $tradeName,

        #[V\Required, V\Cnpj(allowAlphanumeric: true, stripMask: true)]
        public string $cnpj,

        #[V\Required, V\Phone(country: 'BR', stripMask: true)]
        public string $phone,

        #[V\Required, V\Email]
        public string $billingEmail,

        #[V\Optional, V\Url]
        public ?string $website = null,
    ) {}
}
```

---

## Example 3: Secure Document Upload Pipeline

```php
use Arbor\Validator\AV;
use Arbor\Validator\Files\UploadedFile;

$documentSchema = AV::shape([
    'national_id_front' => AV::file()
        ->maxSize('10MB')
        ->extension(['pdf', 'jpg', 'jpeg', 'png'])
        ->mimeType(['application/pdf', 'image/jpeg', 'image/png']),
    'proof_of_residence' => AV::file()
        ->maxSize('10MB')
        ->extension(['pdf'])
        ->mimeType(['application/pdf']),
]);

$result = $documentSchema->safeParse($_FILES);

if ($result->failed()) {
    return Response::json(['errors' => $result->errors()], 422);
}

/** @var array<string, UploadedFile> $files */
$files = $result->data();

$storageDir = '/var/www/secure_storage/';
$files['national_id_front']->moveTo($storageDir . 'id_front_' . uniqid() . '.pdf');
```
