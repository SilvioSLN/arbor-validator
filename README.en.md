# 🌳 Arbor Validator

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](tests/)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-8892BF.svg)](composer.json)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Dependencies](https://img.shields.io/badge/dependencies-0-success.svg)](composer.json)
[![AI Ready](https://img.shields.io/badge/AI-Ready-8A2BE2.svg)](AGENTS.md)

*Read this in [Português (Brasil)](README.md) | AI Agents Guide: [AGENTS.md](AGENTS.md)*

**Arbor Validator** is a modern validation library for PHP 8.3+, framework-agnostic with **zero external runtime dependencies**. Inspired by the Developer Experience (DX) of **Zod** (TypeScript) and modern PHP's native typing, reflection, and first-class attributes.

It solves four essential challenges when accepting data:
1. **Rigorous Validation:** Strict rules including specialized Brazilian validations (official Modulo 11 CPF and the **new 2024 RFB Alphanumeric CNPJ standard**).
2. **Secure File / Upload Validation:** True file validation (`$_FILES`) checking size, extension, and **real server MIME type (magic bytes)** via `finfo_file` to eliminate extension spoofing attacks.
3. **Smart Coercion & Pipelines:** Intelligent coercion for HTTP forms/JSON alongside transformation pipelines with `.transform()` and `.preprocess()`.
4. **Strongly Typed Output Mapping:** Returns 100% type-safe data, either as an **instantiated DTO (Typed Class)** or as a **Sanitized Associative Array**.

---

## 📦 Installation

```bash
composer require silviosln/arbor-validator
```

### Requirements
* **PHP 8.3** or higher (`declare(strict_types=1);`, `readonly class`, constructor property promotion, first-class attributes)
* Native extensions: `ext-mbstring`, `ext-json`, `ext-fileinfo`
* **Zero third-party dependencies** in production.

---

## 🛠 Two First-Class Paradigms

Arbor Validator supports two distinct validation paradigms sharing the exact same underlying validation and coercion engine:

### 1. Class-First (DTOs with PHP 8 Attributes)
Ideal for REST APIs, Domain-Driven Design (DDD), and complex forms. The PHP class itself serves as the schema contract:

```php
use Arbor\Validator\Attributes as V;
use Arbor\Validator\ArborValidator;

final readonly class RegisterUserDTO
{
    public function __construct(
        #[V\Required, V\FullName]
        public string $name,

        #[V\Required, V\Email]
        public string $email,

        #[V\Required, V\MinLength(8)]
        public string $password,

        #[V\Required, V\SameAs('password', message: 'Passwords must match')]
        public string $passwordConfirmation,

        #[V\Required, V\Cpf(stripMask: true)]
        public string $cpf,

        #[V\Required, V\Phone(format: 'BR', stripMask: true)]
        public string $phone,

        #[V\Optional, V\Cnpj(allowAlphanumeric: true, stripMask: true)]
        public ?string $cnpj = null,

        #[V\Optional, V\Date(format: 'Y-m-d')]
        public ?\DateTimeImmutable $birthDate = null,

        #[V\Optional, V\Time(format: 'H:i')]
        public ?string $preferredContactTime = null,

        #[V\Optional, V\Domain]
        public ?string $websiteDomain = null,

        #[V\Optional, V\Url]
        public ?string $profileUrl = null,

        #[V\Optional, V\Uuid]
        public ?string $affiliateUuid = null,

        #[V\Optional, V\NoHtml]
        public ?string $bio = null,

        #[V\Optional, V\UploadedFile(
            maxSize: '5MB',
            extensions: ['jpg', 'jpeg', 'png', 'webp'],
            mimeTypes: ['image/jpeg', 'image/png', 'image/webp']
        )]
        public ?array $avatar = null,
    ) {}
}

// Controller / Endpoint Execution:
$result = ArborValidator::validate(RegisterUserDTO::class, array_merge($_POST, $_FILES));

if ($result->failed()) {
    // Returns structured error map: ['cpf' => ['The cpf field contains an invalid CPF.'], ...]
    return response()->json(['errors' => $result->errors()], 422);
}

/** @var RegisterUserDTO $dto */
$dto = $result->data(); // Instantiated, strictly typed DTO ready to pass to your domain
echo $dto->name;
echo $dto->cpf; // '11144477735' (clean digits with stripMask)
echo $dto->birthDate->format('Y-m-d'); // \DateTimeImmutable
```

---

### 2. Fluent Schema / Zod-like Builder
Ideal for quick route handlers, inline micro-validations, and scripts:

```php
use Arbor\Validator\AV;

$registerSchema = AV::shape([
    'name'                  => AV::string()->fullName()->min(3)->max(100),
    'email'                 => AV::string()->email()->transform(fn($e) => strtolower(trim($e))),
    'password'              => AV::string()->min(8),
    'password_confirmation' => AV::string(),
    'cpf'                   => AV::string()->cpf(stripMask: true),
    'cnpj'                  => AV::string()->cnpj(allowAlphanumeric: true, stripMask: true)->optional(),
    'phone'                 => AV::string()->phone('BR', stripMask: true),
    'domain'                => AV::string()->domain()->optional(),
    'url'                   => AV::string()->url()->optional(),
    'time'                  => AV::string()->time('H:i')->optional(),
    'date'                  => AV::string()->date('Y-m-d')->coerceDate(), // Converts to \DateTimeImmutable
    'avatar'                => AV::file()
                                ->maxSize('5MB')
                                ->extension(['jpg', 'png', 'webp'])
                                ->mimeType(['image/jpeg', 'image/png', 'image/webp'])
                                ->optional(),
])
->sameAs('password_confirmation', 'password', 'Passwords must match');

// Safe validation (does not throw)
$result = $registerSchema->safeParse(array_merge($_POST, $_FILES));

if (!$result->success) {
    return response()->json(['errors' => $result->errors()], 422);
}

$cleanData = $result->data(); // Sanitized, type-coerced associative array
```

---

## 🇧🇷 Enterprise Brazilian Validations

### 1. CPF (`AV::string()->cpf()` / `#[V\Cpf]`)
* Official Modulo 11 two-digit checksum validation.
* Rejects repeated digit sequences (`111.111.111-11`, `000.000.000-00`, etc.).
* Accepts formatted (`123.456.789-00`) or raw digits (`12345678900`).
* Native `stripMask: true` support to persist clean digits in databases.

### 2. New 2024 RFB Alphanumeric CNPJ (`AV::string()->cnpj()` / `#[V\Cnpj]`)
* Fully compliant with traditional 14-digit numeric CNPJs and the **Normativa RFB nº 2.229/2024** (new alphanumeric standard).
* Positions 1-12 alphanumeric (`0-9` and `A-Z`), positions 13-14 numeric check digits.
* Official weighted ASCII calculation (`ord(char) - 48`) with Modulo 11.
* Supports `allowAlphanumeric: false` if strict legacy numeric-only validation is required.

### 3. Brazilian & International Phone Numbers (`AV::string()->phone()` / `#[V\Phone]`)
* Validates Brazilian mobile numbers (11 digits with mandatory 9th digit `'9'`) and landlines (10 digits starting with `2`, `3`, `4`, or `5`).
* Validates against official Brazilian DDD (area code) tables, rejecting nonexistent codes (`00`, `20`, etc.).
* Supports E.164 international format (`+5511999998888`) and automatic mask stripping.

---

## 📁 True Uploaded File Validation (`$_FILES`)

Many libraries naively trust `$_FILES['avatar']['type']`, which is an untrusted client header that can easily disguise malicious scripts (e.g. `exploit.php` with `Content-Type: image/jpeg`).

**Arbor Validator** inspects the **actual magic bytes** of the file on the server using PHP's native `finfo_file()`:

```php
AV::file()
    ->maxSize('5MB') // Converts readable strings ('500KB', '2GB') into byte integers
    ->minSize('10KB')
    ->extension(['jpg', 'png', 'pdf'])
    ->mimeType(['image/jpeg', 'image/png', 'application/pdf']);
```

Manipulate validated files safely with the `UploadedFile` object:
```php
$file = $result->data()['avatar']; // Instance of Arbor\Validator\Files\UploadedFile

echo $file->clientName();     // "photo.jpg"
echo $file->mimeType();       // "image/jpeg" (inspected via magic bytes)
echo $file->extension();      // "jpg"
echo $file->size();           // 204800 (bytes)

// Atomically move to permanent storage (creates parent directories if needed)
$file->moveTo('/var/www/uploads/avatar_123.jpg');
```

---

## 🧱 Nested DTOs and Lists (`#[V\Nested]`, `#[V\Each]`)

Map hierarchical structures with dotted path error reporting (`address.street`, `items.0.price`):

```php
use Arbor\Validator\Attributes as V;

final readonly class OrderDTO
{
    public function __construct(
        #[V\Required]
        public string $customer,

        #[V\Required, V\Nested(AddressDTO::class)]
        public AddressDTO $address,

        /** @var list<OrderItemDTO> */
        #[V\Required, V\Each(OrderItemDTO::class)]
        public array $items,
    ) {}
}

$result = ArborValidator::validate(OrderDTO::class, $requestData);

if ($result->failed()) {
    // Structured dotted errors:
    // [
    //     'address.street' => ['The address.street field is required.'],
    //     'items.0.price'  => ['The items.0.price field must be a number.']
    // ]
    return response()->json(['errors' => $result->errors()], 422);
}
```

---

## 🛡 First-Class `ValidationResult` API

```php
$result = ArborValidator::validate($dtoOrSchema, $data);

$result->isValid();               // bool (true on success)
$result->failed();                // bool (true on error)
$result->data();                  // Returns DTO or clean array (throws ValidationException if failed)
$result->safeData();              // Returns data without throwing
$result->errors();                // array<string, list<string>>
$result->firstError();            // ?string (first global error)
$result->error('cpf');            // ?string (first error of field)
$result->fieldErrors('cpf');      // list<string> (all errors of field)
$result->hasError('cpf');         // bool
```

---

## ⚡ Safe Mode (`validate`) vs Exception Mode (`parse`)

```php
use Arbor\Validator\ArborValidator;
use Arbor\Validator\Exceptions\ValidationException;

try {
    $dto = ArborValidator::parse(RegisterUserDTO::class, $_POST);
    // 100% valid DTO...
} catch (ValidationException $e) {
    return response()->json([
        'message' => $e->getMessage(),
        'errors'  => $e->errors(),
    ], 422);
}
```

---

## 🌐 Internationalization (i18n)

Error messages are localized into **Português (pt-BR)** and **English (en)**:

```php
use Arbor\Validator\ArborValidator;

// Switch global locale to English
ArborValidator::setLocale('en');

// Register or override translations
ArborValidator::addMessages('en', [
    'cpf' => 'The provided CPF is invalid according to Federal Revenue standards.',
]);
```

---

## 🤖 AI Agents & Coding Assistants

Arbor Validator is engineered to be **AI-Native**:
* **[AGENTS.md](AGENTS.md)**: Operational rules and anti-hallucination guardrails for AI coding agents.
* **[ai/](ai/)**: Complete AI knowledge base containing [architecture](ai/architecture.md), [API reference](ai/api.md), [task workflows](ai/workflows.md), [patterns](ai/patterns.md), [anti-patterns](ai/anti-patterns.md), [troubleshooting](ai/troubleshooting.md), and [machine-readable JSON schema](ai/reference.json).

---

## 🧪 Testing & Quality

```bash
# Run unit and feature tests
composer test

# Generate HTML code coverage report
composer test:coverage

# Static type analysis (Level 6)
composer phpstan
```

---

## 📄 License

Distributed under the MIT License. See [LICENSE](LICENSE) for more information.
