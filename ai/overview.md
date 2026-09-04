# Overview: Arbor Validator

## 1. Mission and Problem Statement

In the PHP ecosystem, input validation has historically been fragmented:
- **Framework Validators (e.g. Laravel / Symfony)** are heavy, tightly coupled to their respective ecosystems, and frequently rely on string-based rules (`'required|email|min:8'`) which lack static analysis support, autocomplete, and compile-time safety.
- **Micro-libraries** often lack essential real-world features: native Brazilian legal validations (CPF, the new 2024 RFB alphanumeric CNPJ), secure file inspection (magic bytes vs spoofed client MIME types), and nested DTO reflection.
- **TypeScript Developers** have long enjoyed libraries like **Zod**, which merge schema validation, transformation, coercion, and static type inference into a seamless developer experience (DX).

**Arbor Validator** was built to bridge this gap in modern PHP 8.3+:
1. **Zero External Dependencies**: Operates with zero third-party packages in production, ensuring fast installations, no dependency conflicts, and long-term stability.
2. **Dual-Paradigm Design**:
   - **Class-First DTO**: Uses native PHP 8 Attributes (`#[V\Required]`, `#[V\Email]`, `#[V\Cpf]`) on `readonly class` structures for full IDE autocompletion, static analysis, and Domain-Driven Design (DDD).
   - **Fluent Schemas (Zod-like)**: Uses chainable schemas (`AV::shape([...])`, `AV::string()->trim()->email()`) for rapid scripts, inline endpoints, and functional transformations.
3. **Enterprise Brazilian Validations**: Native, accurate implementations of CPF, Phone with DDD, and the **Normativa RFB nº 2.229/2024 (Alphanumeric CNPJ)**.
4. **Real File Security**: Inactive of client-supplied MIME headers; validates uploads by inspecting file magic bytes via `finfo_file()`.

---

## 2. Target Audience

- **Modern PHP Developers (PHP 8.3+)**: Engineers building APIs, microservices, CLI tools, or web applications with strict types (`declare(strict_types=1);`).
- **Domain-Driven Design (DDD) & Clean Architecture Practitioners**: Teams requiring strongly typed DTOs as boundaries between transport and domain layers.
- **Brazilian Software Developers**: Applications processing CPF, CNPJ, and Brazilian phone numbers without needing external unmaintained helper packages.
- **Framework-Agnostic Projects**: Applications using lightweight routers (Arbor Router, Slim, RoadRunner, FrankenPHP) or standalone CLI workers.

---

## 3. What Arbor Validator IS vs What it IS NOT

### Arbor Validator IS:
- A **strict validation engine** with high-performance parsing and error localization.
- A **type-coercion pipeline** that safely casts HTTP string inputs (`"1"`, `"true"`, `"1995-04-12"`) to native PHP types (`int`, `bool`, `\DateTimeImmutable`).
- A **DTO mapper** that instantiates readonly classes from raw input with constructor injection and dotted error paths.
- A **security guard** for file uploads that detects file spoofing.
- An **internationalized library** supporting `pt-BR` and `en` out of the box with custom message registration.

### Arbor Validator IS NOT:
- An **ORM or Database Layer**: It does not query databases or check uniqueness (`unique:users,email`). Uniqueness checks belong in application/domain services.
- An **HTTP Framework or Router**: It provides an integration trait (`ValidatesRequestTrait`), but does not handle routing, sessions, or HTTP response rendering.
- A **Sanitization-only Library**: While attributes like `#[V\Html(sanitize: true)]` and methods like `trim()` exist, its primary job is invariant assertion and type mapping.

---

## 4. Key Constraints & Guarantees

1. **PHP 8.3+ Only**: Leverages readonly properties, typed class constants, first-class attributes, and constructor promotion.
2. **Zero Inventions**: All APIs described in this AI context layer exist in `src/` and are validated by automated test suites.
3. **Backward Compatibility**: Public APIs (`ArborValidator`, `AV`, `ValidationResult`, `UploadedFile`, attributes) maintain strict semantic stability.
