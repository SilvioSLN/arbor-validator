# Task-Oriented Workflows: Arbor Validator

This guide provides concrete, step-by-step recipes for common real-world development tasks.

---

## Workflow 1: Validating an API Request with a Class-First DTO

### Goal
Receive JSON/form data in a controller, validate constraints, and get an instantiated, type-safe DTO.

### Step 1: Declare the DTO Class
```php
namespace App\DTOs;

use Arbor\Validator\Attributes as V;

final readonly class RegisterUserDTO
{
    public function __construct(
        #[V\Required, V\FullName]
        public string $name,

        #[V\Required, V\Email]
        public string $email,

        #[V\Required, V\MinLength(8)]
        public string $password,

        #[V\Required, V\SameAs('password', message: 'As senhas não conferem')]
        public string $passwordConfirmation,

        #[V\Required, V\Cpf(stripMask: true)]
        public string $cpf,

        #[V\Required, V\Phone(country: 'BR', stripMask: true)]
        public string $phone,

        #[V\Optional, V\Date(format: 'Y-m-d')]
        public ?\DateTimeImmutable $birthDate = null,
    ) {}
}
```

### Step 2: Validate in Controller Action
```php
use App\DTOs\RegisterUserDTO;
use Arbor\Validator\ArborValidator;

class UserController
{
    public function register(Request $request): Response
    {
        $result = ArborValidator::validate(RegisterUserDTO::class, $request->all());

        if ($result->failed()) {
            return Response::json([
                'message' => 'Validation failed',
                'errors'  => $result->errors(),
            ], 422);
        }

        /** @var RegisterUserDTO $dto */
        $dto = $result->data();

        // Pass clean, strongly typed DTO to Domain Service
        $user = $this->userService->create($dto);

        return Response::json(['id' => $user->id], 201);
    }
}
```

---

## Workflow 2: Validating Brazilian Documents & Contacts

### Goal
Validate CPF, official 2024 RFB alphanumeric CNPJ, and Brazilian phone numbers with mask stripping.

```php
use Arbor\Validator\AV;

$companySchema = AV::shape([
    // Official RFB 2024 format + traditional numeric format
    'cnpj' => AV::string()->cnpj(allowAlphanumeric: true, stripMask: true),
    
    // Official CPF with Modulo 11 and repeated digits check
    'representative_cpf' => AV::string()->cpf(stripMask: true),
    
    // Official DDD verification + 9-digit mobile check
    'contact_phone' => AV::string()->phone(country: 'BR', stripMask: true),
]);

$result = $companySchema->safeParse([
    'cnpj'               => '12.ABC.345/01DE-35',
    'representative_cpf' => '111.444.777-35',
    'contact_phone'      => '(11) 98765-4321',
]);

if ($result->isValid()) {
    $data = $result->data();
    // $data['cnpj'] is '12ABC34501DE35'
    // $data['representative_cpf'] is '11144477735'
    // $data['contact_phone'] is '11987654321'
}
```

---

## Workflow 3: Securely Validating and Saving File Uploads

### Goal
Validate user avatar uploads, ensuring genuine image bytes (preventing script spoofing) and atomic file moves.

```php
use Arbor\Validator\AV;
use Arbor\Validator\Files\UploadedFile;

$uploadSchema = AV::shape([
    'avatar' => AV::file()
        ->maxSize('2MB')
        ->extension(['jpg', 'jpeg', 'png', 'webp'])
        ->mimeType(['image/jpeg', 'image/png', 'image/webp']),
]);

$result = $uploadSchema->safeParse($_FILES);

if ($result->failed()) {
    return Response::json(['error' => $result->firstError()], 422);
}

/** @var UploadedFile $file */
$file = $result->data()['avatar'];

$newFilename = 'avatar_' . uniqid() . '.' . $file->extension();
$destination = __DIR__ . '/../storage/avatars/' . $newFilename;

$file->moveTo($destination);
```

---

## Workflow 4: Validating Nested DTOs and Dynamic Lists

### Goal
Validate complex checkout orders with customer address and multiple dynamic line items.

```php
namespace App\DTOs;

use Arbor\Validator\Attributes as V;

final readonly class AddressDTO
{
    public function __construct(
        #[V\Required] public string $street,
        #[V\Required] public string $city,
        #[V\Required] public string $zipCode,
    ) {}
}

final readonly class ItemDTO
{
    public function __construct(
        #[V\Required] public string $sku,
        #[V\Required, V\MinLength(1)] public int $quantity,
        #[V\Required] public float $price,
    ) {}
}

final readonly class OrderDTO
{
    public function __construct(
        #[V\Required] public string $orderId,
        #[V\Required, V\Nested(AddressDTO::class)] public AddressDTO $address,
        /** @var list<ItemDTO> */
        #[V\Required, V\Each(ItemDTO::class)] public array $items,
    ) {}
}

// In Controller:
$result = ArborValidator::validate(OrderDTO::class, $payload);
// If an item has invalid price, error key is 'items.0.price'
```

---

## Workflow 5: Integrating with Custom Router / PSR Request

### Goal
Add validation capability directly to an HTTP Request class.

```php
namespace App\Http;

use Arbor\Validator\Integration\ValidatesRequestTrait;

class Request
{
    use ValidatesRequestTrait;

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public function files(): array
    {
        return $_FILES;
    }
}

// Inside route action callback:
$router->post('/users', function (Request $request) {
    // 1. Safe mode:
    $result = $request->validate(CreateUserDTO::class);
    if ($result->failed()) {
        return Response::json(['errors' => $result->errors()], 422);
    }
    
    // 2. Or Exception mode:
    // $dto = $request->validateOrFail(CreateUserDTO::class);
});
```

---

## Workflow 6: Creating a Custom Validation Rule

### Goal
Validate a proprietary Brazilian state tax ID (Inscrição Estadual) or company code.

### Step 1: Implement `RuleInterface`
```php
namespace App\Rules;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\RuleInterface;

class StateRegistrationRule implements RuleInterface
{
    public function __construct(
        private readonly string $state,
        private readonly ?string $message = null,
    ) {}

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if (!is_string($value) || strlen($value) < 8) {
            $context->addError($this->message ?? "Inscrição Estadual inválida para o estado {$this->state}.");
            return false;
        }

        return true;
    }
}
```

### Step 2: (Optional) Create DTO Attribute
```php
namespace App\Attributes;

use App\Rules\StateRegistrationRule;
use Arbor\Validator\Attributes\ValidationAttributeInterface;
use Arbor\Validator\Core\ValidationContext;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class StateRegistration implements ValidationAttributeInterface
{
    public function __construct(
        public string $state = 'SP',
        public ?string $message = null,
    ) {}

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new StateRegistrationRule($this->state, $this->message);
        return $rule->validate($value, $context);
    }
}
```
