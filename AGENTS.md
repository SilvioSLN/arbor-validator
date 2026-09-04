# Instructions for AI Programming Agents (AGENTS.md)

This document provides explicit technical guidelines for AI coding agents (Claude, Cursor, Copilot, Antigravity, Aider, Windsurf) integrating, using, or maintaining **Arbor Validator** (`silviosln/arbor-validator`).

---

## 1. Library Overview

- **Package**: `silviosln/arbor-validator`
- **PHP Version**: `^8.3` (`declare(strict_types=1);`, `readonly class`, constructor property promotion, first-class native attributes).
- **Dependencies**: **Zero** external runtime dependencies (relies only on PHP core and `ext-fileinfo`, `ext-mbstring`, `ext-json`).
- **Core Philosophy**: Modern validation engine providing **two first-class paradigms**:
  1. **Class-First DTOs**: PHP 8 Attributes (`#[V\Required]`, `#[V\Email]`, `#[V\Cpf]`, etc.) on strongly typed `readonly class` DTOs.
  2. **Fluent Schemas (Zod-like)**: Chainable schema builders (`AV::string()->email()`, `AV::shape([...])`, `AV::coerce()->int()`).

---

## 2. Decision Tree: Which Paradigm to Use?

Follow these guidelines when generating code:

```text
Are you handling an HTTP Endpoint, API Controller, or Domain Service?
├── YES -> Use Class-First DTO (Paradigm 1)
│          - Create a `final readonly class MyDTO`
│          - Add attributes: `use Arbor\Validator\Attributes as V;`
│          - Validate via: `ArborValidator::validate(MyDTO::class, $payload)`
│          - Returns an instantiated, type-safe DTO instance on success.
│
└── NO (Inline script, CLI utility, ad-hoc transform, micro-validation)
        -> Use Fluent Schema (Paradigm 2)
           - Build schema via: `AV::shape([...])`
           - Validate via: `$schema->safeParse($payload)`
           - Returns a clean associative array on success.
```

---

## 3. Critical Rules & Anti-Hallucinations (NEVER Do This)

| NEVER DO (Hallucination) | ALWAYS DO (Arbor Validator API) | Reason |
| :--- | :--- | :--- |
| `$result->fails()` | `$result->failed()` or `!$result->isValid()` | Arbor Validator uses `isValid()` and `failed()`. |
| `$result->data` | `$result->data()` or `$result->safeData()` | Methods, not public properties. Note: `data()` throws if validation failed! |
| `$request->validate([...])` (Laravel style) | `$request->validate(UserDTO::class)` or `$request->validate(AV::shape(...))` | Arbor's `ValidatesRequestTrait` accepts a DTO class, a `Schema`, or a schema array. |
| Trusting `$_FILES['type']` | Use `AV::file()->mimeType(...)` or `#[V\UploadedFile(mimeTypes: [...])]` | Arbor checks real magic bytes via `finfo_file()` to prevent extension spoofing. |
| Instantiating rules directly in controllers (`new CpfRule()`) | Use `AV::string()->cpf()` or `#[V\Cpf]` | Higher-level facades handle sanitization, context, and error localization. |
| `$validator->errors()->first()` | `$result->firstError()` or `$result->error('field')` | Built-in convenience methods on `ValidationResult`. |
| Inventing non-existent schema methods (`AV::uuid()`) | `AV::string()->uuid()` | `uuid()`, `email()`, `cpf()`, `cnpj()`, `phone()` are methods on `StringSchema`. |

---

## 4. Preferred APIs & Common Patterns

### Paradigm 1: Class-First DTO (Recommended for APIs & Controllers)

```php
use Arbor\Validator\ArborValidator;
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

        #[V\Required, V\SameAs('password', message: 'Passwords must match')]
        public string $passwordConfirmation,

        #[V\Required, V\Cpf(stripMask: true)]
        public string $cpf,

        #[V\Required, V\Phone(country: 'BR', stripMask: true)]
        public string $phone,

        #[V\Optional, V\Date(format: 'Y-m-d')]
        public ?\DateTimeImmutable $birthDate = null,
    ) {}
}

// 1. Safe Mode (Recommended for Web Controllers)
$result = ArborValidator::validate(RegisterUserDTO::class, $requestData);

if ($result->failed()) {
    // Returns HTTP 422 with structured errors
    return response()->json(['errors' => $result->errors()], 422);
}

/** @var RegisterUserDTO $dto */
$dto = $result->data(); // Instantiated and validated DTO
```

### Paradigm 2: Fluent Schema (Zod-like)

```php
use Arbor\Validator\AV;

$schema = AV::shape([
    'name'     => AV::string()->fullName(),
    'email'    => AV::string()->email()->lowercase(),
    'age'      => AV::coerce()->int()->min(18),
    'cpf'      => AV::string()->cpf(stripMask: true),
    'cnpj'     => AV::string()->cnpj(allowAlphanumeric: true, stripMask: true)->optional(),
    'website'  => AV::string()->url()->optional(),
    'role'     => AV::enum(['admin', 'editor', 'viewer'])->default('viewer'),
]);

$result = $schema->safeParse($payload);

if ($result->isValid()) {
    $cleanData = $result->data(); // Sanitized associative array
} else {
    $errors = $result->errors(); // ['field' => ['Error message 1', ...]]
}
```

---

## 5. Safe Mode vs Exception Mode

- **Safe Mode**: Use `ArborValidator::validate()` or `$schema->safeParse()`. Never throws on invalid data. Always check `$result->isValid()` or `$result->failed()` before calling `$result->data()`.
- **Exception Mode**: Use `ArborValidator::parse()` or `$schema->parse()`. Throws `Arbor\Validator\Exceptions\ValidationException` on failure.
  ```php
  use Arbor\Validator\ArborValidator;
  use Arbor\Validator\Exceptions\ValidationException;

  try {
      $dto = ArborValidator::parse(CreateOrderDTO::class, $payload);
  } catch (ValidationException $e) {
      $statusCode = $e->getCode(); // 422
      $errors = $e->errors();      // array<string, list<string>>
  }
  ```

---

## 6. Brazilian Validations Guide

Arbor Validator includes native Brazilian validators with modern official specifications:

1. **CPF**:
   - DTO: `#[V\Cpf(stripMask: true)]`
   - Schema: `AV::string()->cpf(stripMask: true)`
   - Features: Modulo 11 check digits, repeated sequences rejection (`111.111.111-11`), mask stripping.
2. **CNPJ**:
   - DTO: `#[V\Cnpj(allowAlphanumeric: true, stripMask: true)]`
   - Schema: `AV::string()->cnpj(allowAlphanumeric: true, stripMask: true)`
   - Features: Fully compliant with **Normativa RFB nº 2.229/2024** (alphanumeric CNPJ in positions 1-12, numeric check digits in positions 13-14) AND traditional numeric 14-digit CNPJs.
3. **Phone**:
   - DTO: `#[V\Phone(country: 'BR', stripMask: true)]`
   - Schema: `AV::string()->phone('BR', stripMask: true)`
   - Features: Validates official Brazilian DDDs table (rejects invalid DDDs like 00, 20), mobile phones (11 digits, 9th digit = 9), landlines (10 digits), E.164 (`+55...`), and mask stripping.

---

## 7. File Uploads & Magic Bytes Security

- Use `#[V\UploadedFile]` or `AV::file()`.
- Validates real MIME types by inspecting **magic bytes** via `finfo_file()`, never relying solely on client-reported headers.
- Testing Mode: In CLI scripts, seeders, or unit tests, activate testing mode to validate non-HTTP uploads:
  ```php
  ArborValidator::setTestingMode(true);
  ```
- Uploaded file object methods:
  - `$file->clientName()` / `$file->getClientFilename()`: Original filename.
  - `$file->mimeType()` / `$file->getRealMimeType()`: Real server-inspected MIME.
  - `$file->extension()`: File extension from client name in lowercase.
  - `$file->size()` / `$file->getSize()`: File size in bytes.
  - `$file->moveTo('/path/to/destination.jpg')`: Secure atomic move.

---

## 8. Deep-Dive Context Index

For exhaustive documentation and structured manifests, read the following files in the `ai/` folder:

- [`ai/overview.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/overview.md) — What Arbor Validator is and is not, design goals.
- [`ai/architecture.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/architecture.md) — Engine internals (`ClassMapper`, `Coercer`, `ValidationContext`, `ErrorBag`).
- [`ai/api.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/api.md) — Exhaustive API reference of all classes, attributes, schemas, methods, and parameters.
- [`ai/workflows.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/workflows.md) — Step-by-step implementation recipes for common tasks.
- [`ai/configuration.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/configuration.md) — i18n (`pt-BR`, `en`), custom messages, and custom rule development.
- [`ai/errors.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/errors.md) — Error structures, dotted paths, and translation placeholders.
- [`ai/patterns.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/patterns.md) — Idiomatic architectural patterns and best practices.
- [`ai/anti-patterns.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/anti-patterns.md) — Anti-patterns and pitfalls to avoid.
- [`ai/troubleshooting.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/troubleshooting.md) — Quick solutions for common issues.
- [`ai/examples.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/examples.md) — Full, copy-pasteable real-world examples.
- [`ai/reference.md`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/reference.md) — Condensed tabular reference.
- [`ai/reference.json`](file:///home/silvionascimento/Documentos/ns/anulis/opensources/arbor-validator/ai/reference.json) — Machine-readable structured JSON catalog of all public APIs.
