# Complete API Reference: Arbor Validator

This document provides the exhaustive API reference for all public classes, methods, parameters, return types, and exceptions in **Arbor Validator**.

---

## Table of Contents

1. [Main Facade (`ArborValidator`)](#1-main-facade-arborvalidator)
2. [Fluent Factory (`AV` & `CoerceBuilder`)](#2-fluent-factory-av--coercebuilder)
3. [Result Handling (`ValidationResult`)](#3-result-handling-validationresult)
4. [Exceptions (`ValidationException`, `ValidatorException`)](#4-exceptions-validationexception-validatorexception)
5. [File Handling (`UploadedFile`)](#5-file-handling-uploadedfile)
6. [Request Integration Trait (`ValidatesRequestTrait`)](#6-request-integration-trait-validatesrequesttrait)
7. [Schemas Reference](#7-schemas-reference)
8. [Attributes Reference (`#[V\...]`)](#8-attributes-reference-v)
9. [Rules Reference (`RuleInterface`)](#9-rules-reference-ruleinterface)

---

## 1. Main Facade (`ArborValidator`)

**Namespace**: `Arbor\Validator\ArborValidator`

### Methods

#### `validate(string|Schema|array $target, mixed $data, ?string $locale = null): ValidationResult`
- **Description**: Validates input data against a target DTO class, a `Schema` instance, or an associative shape array. Returns a `ValidationResult` without throwing exceptions.
- **Parameters**:
  - `$target` (`class-string<T>|Schema|array<string, Schema>`): Target specification.
  - `$data` (`mixed`): Raw input data (typically associative array from `$_POST`, `json_decode`, etc.).
  - `$locale` (`string|null`): Optional locale override (e.g. `'pt-BR'`, `'en'`).
- **Return**: `ValidationResult`.
- **Throws**: None.

#### `parse(string|Schema|array $target, mixed $data, ?string $locale = null): mixed`
- **Description**: Validates input and returns clean data or instantiated DTO. Throws `ValidationException` on failure.
- **Return**: `T` (if target is DTO class) or `mixed` (sanitized array/scalar).
- **Throws**: `Arbor\Validator\Exceptions\ValidationException`.

#### `setLocale(string $locale): void`
- **Description**: Sets default global locale (`'pt-BR'`, `'en'`).

#### `addMessages(string $locale, array $messages): void`
- **Description**: Adds or overrides message translations in the specified locale catalog.
- **Parameters**:
  - `$locale` (`string`): e.g. `'pt-BR'`, `'en'`.
  - `$messages` (`array<string, string>`): Map of key to message template.

#### `setTestingMode(bool $enabled = true): void`
- **Description**: Toggles testing mode for uploaded files (allows CLI/PHPUnit mock testing without HTTP upload verification).

---

## 2. Fluent Factory (`AV` & `CoerceBuilder`)

**Namespace**: `Arbor\Validator\AV`

### `AV` Static Methods
- `AV::string(): StringSchema`: Creates a string schema.
- `AV::int(): IntSchema`: Creates an integer schema.
- `AV::float(): FloatSchema`: Creates a float schema.
- `AV::number(): NumberSchema`: Creates a general number schema (accepts int or float).
- `AV::bool(): BoolSchema`: Creates a boolean schema.
- `AV::boolean(): BoolSchema`: Alias for `bool()`.
- `AV::shape(array<string, Schema> $fields): ShapeSchema`: Creates an associative object schema.
- `AV::array(?Schema $schema = null): ArraySchema`: Creates a list schema with optional item schema.
- `AV::enum(array|string $cases): EnumSchema`: Creates an enum schema from an array of values or `BackedEnum` class.
- `AV::file(): FileSchema`: Creates an uploaded file schema.
- `AV::preprocess(callable $fn, Schema $schema): PreprocessSchema`: Preprocesses value through `$fn` before passing to inner schema.
- `AV::coerce(): CoerceBuilder`: Returns a `CoerceBuilder` with automatic coercion active.

### `CoerceBuilder` Methods
- `string(): StringSchema`: Casts scalar to string.
- `int(): IntSchema`: Coerces numeric strings/floats to int.
- `float(): FloatSchema`: Coerces strings (handling commas) to float.
- `number(): NumberSchema`: Coerces strings to float/int.
- `bool(): BoolSchema`: Coerces `'true'`, `'1'`, `'yes'`, `'s'`, `'sim'` to `true`, and `'false'`, `'0'`, `'no'`, `'n'`, `'não'` to `false`.
- `date(string $format = 'Y-m-d'): StringSchema`: Validates and coerces string to `\DateTimeImmutable`.

---

## 3. Result Handling (`ValidationResult`)

**Namespace**: `Arbor\Validator\Core\ValidationResult`

### Properties
- `public readonly bool $success`: `true` if valid, `false` otherwise.

### Methods
- `isValid(): bool`: Returns `true` if valid.
- `failed(): bool`: Returns `true` if there are errors.
- `data(): mixed`: Returns validated data (DTO or array). **Throws `ValidationException` if validation failed.**
- `safeData(): mixed`: Returns data without throwing, even if validation failed.
- `errors(): array<string, list<string>>`: Returns all errors grouped by field.
- `firstError(): ?string`: Returns the first error message across all fields.
- `error(string $field): ?string`: Returns the first error message for a specific field.
- `fieldErrors(string $field): list<string>`: Returns all error messages for a specific field.
- `hasError(string $field): bool`: Returns whether a specific field has errors.

---

## 4. Exceptions (`ValidationException`, `ValidatorException`)

**Namespace**: `Arbor\Validator\Exceptions`

### `ValidationException`
- Extends `ValidatorException`.
- **Default HTTP code**: `422`.
- **Properties**:
  - `public readonly array $errors`: `array<string, list<string>>`.
  - `public readonly ?ValidationResult $result`: Original result instance.
- **Methods**:
  - `errors(): array<string, list<string>>`: Error map.
  - `firstError(): ?string`: First error message.
  - `error(string $field): ?string`: First error of a specific field.

### `ValidatorException`
- Extends `\Exception`. Base exception for configuration or internal file system errors.

---

## 5. File Handling (`UploadedFile`)

**Namespace**: `Arbor\Validator\Files\UploadedFile`

### Factory
- `UploadedFile::fromArray(array $file): self`: Creates instance from standard `$_FILES['key']` structure (`tmp_name`, `name`, `size`, `error`, `type`).

### Methods
- `isValid(): bool`: Verifies upload status and file existence.
- `clientName(): string` / `getClientFilename(): string`: Original filename from user.
- `mimeType(): string` / `getRealMimeType(): string`: Real MIME inspected via server magic bytes (`finfo_file`).
- `getClientMimeType(): ?string`: Raw client-supplied MIME header (untrusted).
- `extension(): string` / `getExtension(): string`: Lowercase extension extracted from client filename.
- `size(): int` / `getSize(): int`: File size in bytes.
- `getRealPath(): string`: Path to temp file.
- `moveTo(string $targetPath): bool`: Moves file to destination, automatically creating parent directories if needed.
- `toArray(): array`: Serializes back to `$_FILES` array format.
- `static parseSizeToBytes(string|int $size): int`: Converts strings like `'5MB'`, `'500KB'`, `'2GB'` to bytes.

---

## 6. Request Integration Trait (`ValidatesRequestTrait`)

**Namespace**: `Arbor\Validator\Integration\ValidatesRequestTrait`

Intended for use in Request objects or controllers (e.g., Arbor Router, Slim, Laravel, PSR-7):
- `validate(string|Schema|array $target, ?array $customData = null): ValidationResult`
- `validateOrFail(string|Schema|array $target, ?array $customData = null): mixed` (Throws `ValidationException`)
- `extractRequestPayload(): array`: Automatically merges input body and uploaded files from `$this->all()`, `$this->inputs()`, `$this->getParsedBody()`, or superglobals.

---

## 7. Schemas Reference

All schemas extend `Arbor\Validator\Schemas\Schema` and share these base builder methods:
- `optional(): static`: Marks field optional (allows `null` or missing).
- `nullable(): static`: Allows `null`.
- `default(mixed $defaultValue): static`: Provides a default value if missing/null.
- `catch(mixed $fallbackValue): static`: Suppresses errors on failure and returns fallback value.
- `transform(callable $fn): static`: Appends transformation callback.
- `refine(callable $check, string $message, ?string $path = null): static`: Custom boolean check.
- `superRefine(callable $fn): static`: Custom context assertion `fn($val, ValidationContext $ctx)`.
- `safeParse(mixed $data, ?string $locale = null): ValidationResult`: Safe evaluation.
- `parse(mixed $data, ?string $locale = null): mixed`: Throws `ValidationException` on error.

### `StringSchema`
- `trim(): static`
- `min(int $min, ?string $message = null): static`
- `max(int $max, ?string $message = null): static`
- `length(int $length, ?string $message = null): static`
- `regex(string $pattern, ?string $message = null): static`
- `email(bool $checkDns = false, ?string $message = null): static`
- `cpf(bool $stripMask = false, ?string $message = null): static`
- `cnpj(bool $allowAlphanumeric = true, bool $stripMask = false, ?string $message = null): static`
- `phone(string $country = 'BR', bool $stripMask = false, ?string $message = null): static`
- `fullName(int $minWords = 2, int $minWordLength = 2, ?string $message = null): static`
- `date(string $format = 'Y-m-d', ?string $message = null): static`
- `time(string $format = 'H:i', ?string $message = null): static`
- `domain(?string $message = null): static`
- `url(array $protocols = ['http', 'https'], ?string $message = null): static`
- `uuid(int $version = 0, ?string $message = null): static`
- `noHtml(?string $message = null): static`
- `html(bool $sanitize = false, ?string $message = null): static`
- `emojis(bool $allow = true, bool $only = false, ?string $message = null): static`
- `lowercase(): static`
- `uppercase(): static`
- `stripMask(): static`
- `coerceDate(string $format = 'Y-m-d'): static`

### `NumberSchema`, `IntSchema`, `FloatSchema`
- `min(int|float $min, ?string $message = null): static`
- `max(int|float $max, ?string $message = null): static`
- `positive(?string $message = null): static`
- `negative(?string $message = null): static`
- `coerce(): static`

### `BoolSchema`
- `coerce(): static`

### `ShapeSchema`
- `sameAs(string $targetField, string $compareField, ?string $message = null): static`
- `pick(list<string> $keys): static`
- `omit(list<string> $keys): static`
- `extend(array<string, Schema> $fields): static`
- `merge(ShapeSchema $other): static`
- `partial(): static`
- `strict(): static`: Disallows unrecognized fields.
- `strip(): static`: Ignores unrecognized fields.

### `ArraySchema`
- `min(int $min): static`
- `max(int $max): static`
- `nonEmpty(): static`

### `EnumSchema`
- Constructor takes `list<string|int|float>` or `class-string<\BackedEnum>`.

### `FileSchema`
- `maxSize(string|int $size): static` (e.g. `'5MB'`, `5242880`)
- `minSize(string|int $size): static`
- `extension(list<string>|string $extensions): static` (e.g. `['jpg', 'png']`)
- `mimeType(list<string>|string $mimeTypes): static` (e.g. `['image/jpeg']`)
- `allowNonUploadedFiles(bool $allow = true): static`

---

## 8. Attributes Reference (`#[V\...]`)

**Namespace**: `Arbor\Validator\Attributes` (import as `use Arbor\Validator\Attributes as V;`)

| Attribute | Parameters | Description |
| :--- | :--- | :--- |
| `#[V\Required]` | `?string $message = null` | Field must be provided and non-empty. |
| `#[V\Optional]` | None | Field is optional; uses default constructor value if omitted. |
| `#[V\Nullable]` | None | Allows `null` values. |
| `#[V\Email]` | `bool $checkDns = false, ?string $message = null` | Validates email address format. |
| `#[V\Cpf]` | `bool $stripMask = false, ?string $message = null` | Validates Brazilian CPF with Modulo 11 and sequence checks. |
| `#[V\Cnpj]` | `bool $allowAlphanumeric = true, bool $stripMask = false, ?string $message = null` | Validates traditional CNPJ and 2024 RFB alphanumeric format. |
| `#[V\Phone]` | `?string $format = null, ?string $country = null, bool $stripMask = false, ?string $message = null` | Validates phone format and official Brazilian DDDs. |
| `#[V\FullName]` | `int $minWords = 2, int $minWordLength = 2, ?string $message = null` | Requires at least `$minWords` words. |
| `#[V\MinLength]` | `int $min, ?string $message = null` | Minimum string character length. |
| `#[V\MaxLength]` | `int $max, ?string $message = null` | Maximum string character length. |
| `#[V\SameAs]` | `string $field, ?string $message = null` | Field must match another field in root payload. |
| `#[V\Date]` | `string $format = 'Y-m-d', ?string $message = null` | Validates date string and coerces to `\DateTimeImmutable`. |
| `#[V\DateTime]` | `string $format = 'Y-m-d H:i:s', ?string $message = null` | Validates datetime string. |
| `#[V\Time]` | `string $format = 'H:i', ?string $message = null` | Validates 24-hour time format. |
| `#[V\Url]` | `array $protocols = ['http', 'https'], ?string $message = null` | Validates URL format with allowed protocols. |
| `#[V\Uuid]` | `int $version = 0, ?string $message = null` | Validates UUID (version 0 = any valid UUID). |
| `#[V\Domain]` | `?string $message = null` | Validates domain name format. |
| `#[V\NoHtml]` | `?string $message = null` | Rejects strings containing HTML or script tags. |
| `#[V\Html]` | `bool $sanitize = false, ?string $message = null` | Validates HTML syntax; optionally sanitizes tags. |
| `#[V\Emojis]` | `bool $allow = true, bool $only = false, ?string $message = null` | Controls presence of emoji characters. |
| `#[V\UploadedFile]` | `string|int|null $maxSize, string|int|null $minSize, array $extensions, array $mimeTypes, bool $allowNonUploadedFiles, ?string $message` | Validates uploaded file upload. |
| `#[V\Nested]` | `?string $dtoClass = null` | Validates child DTO object. Auto-detected from property type if omitted. |
| `#[V\Each]` | `string $type` | Validates array items against scalar type or child DTO class. |
| `#[V\Transform]` | `callable|string $transformer` | Applies transformation function (e.g. `'trim'`, `'strtolower'`). |
| `#[V\Coerce]` | `?string $type = null, ?string $dateFormat = null` | Instructs explicit type coercion. |

---

## 9. Rules Reference (`RuleInterface`)

**Namespace**: `Arbor\Validator\Rules`

All internal rules implement `RuleInterface` (`validate(mixed $value, ValidationContext $context): bool`):
- `CpfRule(bool $stripMask, ?string $message)`
- `CnpjRule(bool $allowAlphanumeric, bool $stripMask, ?string $message)`
- `PhoneRule(string $country, bool $stripMask, ?string $message)`
- `FullNameRule(int $minWords, int $minWordLength, ?string $message)`
- `EmailRule(bool $checkDns, ?string $message)`
- `DateRule(string $format, ?string $message)`
- `TimeRule(string $format, ?string $message)`
- `DomainRule(?string $message)`
- `UrlRule(array $protocols, ?string $message)`
- `UuidRule(int $version, ?string $message)`
- `NoHtmlRule(?string $message)`
- `HtmlRule(bool $sanitize, ?string $message)`
- `EmojisRule(bool $allow, bool $only, ?string $message)`
- `SameAsRule(string $field, ?string $message)`
- `UploadedFileRule(...)`
