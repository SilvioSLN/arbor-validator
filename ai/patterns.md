# Recommended Patterns: Arbor Validator

This guide details idiomatic architectural patterns for using **Arbor Validator** in production applications.

---

## Pattern 1: Strongly Typed `final readonly class` DTOs

Always declare DTOs as `final readonly class` with constructor promotion:

```php
namespace App\DTOs;

use Arbor\Validator\Attributes as V;

final readonly class UpdateUserEmailDTO
{
    public function __construct(
        #[V\Required, V\Email]
        public string $email,

        #[V\Required]
        public string $currentPassword,
    ) {}
}
```

### Why:
- Immutability guarantees thread-safety and prevents accidental property manipulation.
- Static analysis tools (PHPStan, Psalm) can fully analyze usage without false positives.

---

## Pattern 2: Strip Masks at the Validation Boundary

When receiving formatted Brazilian documents or phone numbers, strip masks during validation rather than later in your database repositories or controllers:

```php
#[V\Required, V\Cpf(stripMask: true)]
public string $cpf,

#[V\Required, V\Phone(country: 'BR', stripMask: true)]
public string $phone,
```

### Why:
- Your domain layer receives pure digits (`'11144477735'`), which are ideal for database indexing, search queries, and external API payloads.

---

## Pattern 3: Use Native `\DateTimeImmutable` Types in DTOs

Combine `#[V\Date]` or `#[V\DateTime]` with typed `\DateTimeImmutable` properties:

```php
#[V\Optional, V\Date(format: 'Y-m-d')]
public ?\DateTimeImmutable $birthDate = null,
```

### Why:
- `ClassMapper` will automatically convert valid date strings (e.g. `'1995-04-12'`) into `\DateTimeImmutable` objects via `Coercer`.
- Your domain layer never has to parse strings into date objects manually.

---

## Pattern 4: Controller Guard-Clause Flow

Always structure controller endpoints with an early-return guard clause:

```php
public function store(Request $request): Response
{
    $result = ArborValidator::validate(CreateProductDTO::class, $request->all());

    if ($result->failed()) {
        return Response::json([
            'message' => 'The given data was invalid.',
            'errors'  => $result->errors(),
        ], 422);
    }

    /** @var CreateProductDTO $dto */
    $dto = $result->data();

    $product = $this->productService->create($dto);

    return Response::json($product, 201);
}
```

### Why:
- Keeps happy-path execution un-nested.
- Conforms to RFC 7807 problem details and standard REST error formats.

---

## Pattern 5: Cross-Field Assertions with `SameAs`

Use `#[V\SameAs]` or `.sameAs()` for matching passwords, emails, or date ranges:

```php
// DTO:
#[V\Required]
public string $password,

#[V\Required, V\SameAs('password', message: 'Passwords must match')]
public string $passwordConfirmation,

// Schema:
$schema = AV::shape([
    'password'              => AV::string()->min(8),
    'password_confirmation' => AV::string(),
])->sameAs('password_confirmation', 'password');
```
