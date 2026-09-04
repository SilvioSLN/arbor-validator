# Architecture & Internals: Arbor Validator

This document outlines the architectural blueprint, internal component collaboration, and data flow of **Arbor Validator**.

---

## 1. High-Level Architectural Diagram

```text
                                  +-----------------------+
                                  |       Raw Input       |
                                  | ($_POST, $_FILES, JSON)
                                  +-----------+-----------+
                                              |
                                              v
                            +-----------------------------------+
                            |    ArborValidator Entry Point     |
                            +-----------------+-----------------+
                                              |
                     +------------------------+------------------------+
                     | Target is DTO (class-string)                    | Target is Schema / array
                     v                                                 v
        +---------------------------+                     +---------------------------+
        |   ClassMapper Engine      |                     |    Schema / ShapeSchema   |
        +-------------+-------------+                     +-------------+-------------+
                      |                                                 |
                      +-----------------------+-------------------------+
                                              |
                                              v
                              +-------------------------------+
                              |       ValidationContext       |
                              |  - Current path ('user.email')|
                              |  - Root dataset reference     |
                              |  - ErrorBag aggregator        |
                              |  - Locale context             |
                              +---------------+---------------+
                                              |
                     +------------------------+------------------------+
                     |                                                 |
                     v                                                 v
        +---------------------------+                     +---------------------------+
        |      Rules Execution      |                     |     Coercer & Transforms  |
        |  - ValidationAttribute    |                     |  - Coercer::toInt/toBool  |
        |  - RuleInterface          |                     |  - CoerceDate (DateTime)  |
        |  - finfo magic bytes      |                     |  - Custom .transform()    |
        +-------------+-------------+                     +-------------+-------------+
                      |                                                 |
                      +-----------------------+-------------------------+
                                              |
                                              v
                             +----------------------------------+
                             |   Outcome Evaluation (ErrorBag)  |
                             +----------------+-----------------+
                                              |
                     +------------------------+------------------------+
                     | Has Errors                                      | Zero Errors
                     v                                                 v
        +---------------------------+                     +---------------------------+
        | ValidationResult::failure |                     | ValidationResult::success |
        |  - success: false         |                     |  - success: true          |
        |  - errors: array          |                     |  - data: DTO or clean array
        +---------------------------+                     +---------------------------+
```

---

## 2. Core Components & Responsibilities

### 2.1. `ArborValidator` (`src/ArborValidator.php`)
- **Role**: Static public facade.
- **Methods**:
  - `validate($target, $data, $locale)`: Dispatches to `ClassMapper` (if target is string DTO) or `safeParse()` (if target is `Schema` or associative array). Never throws; returns `ValidationResult`.
  - `parse($target, $data, $locale)`: Calls `validate()` and invokes `->data()`, throwing `ValidationException` on failure.
  - `setLocale($locale)` & `addMessages($locale, $messages)`: Configures global translation catalog.
  - `setTestingMode($enabled)`: Toggles file upload test mode in `ValidationContext`.

### 2.2. `AV` and `CoerceBuilder` (`src/AV.php`, `src/Schemas/CoerceBuilder.php`)
- **Role**: Fluent factory mimicking Zod's API.
- Instantiates schema objects: `StringSchema`, `IntSchema`, `FloatSchema`, `NumberSchema`, `BoolSchema`, `ShapeSchema`, `ArraySchema`, `EnumSchema`, `FileSchema`, `PreprocessSchema`.
- `AV::coerce()` returns a `CoerceBuilder` providing pre-configured schemas with automatic coercion turned on.

### 2.3. `ClassMapper` (`src/Core/ClassMapper.php`)
- **Role**: Reflection-based mapper for PHP 8 DTOs.
- **Key Behaviors**:
  1. Inspects constructor parameters via `ReflectionClass`.
  2. Extracts values from input data, trying `camelCase` first, and falling back automatically to `snake_case` (e.g. `$userFirstName` maps from `user_first_name`).
  3. Collects attributes from constructor parameters and non-promoted properties (preventing duplicate attribute execution on promoted parameters).
  4. Automatically resolves nested DTOs (classes implementing `#[V\Nested]` or typed with a class name).
  5. Automatically handles lists of items typed with `#[V\Each(ItemDTO::class)]`.
  6. Executes `Coercer` to match native parameter types (`int`, `float`, `bool`, `\DateTimeImmutable`).
  7. Instantiates the final DTO via `ReflectionClass::newInstanceArgs()`.

### 2.4. `Coercer` (`src/Core/Coercer.php`)
- **Role**: Pure static utility for reliable type conversions.
- **Conversion Rules**:
  - `toInt(mixed)`: If numeric string or float, casts to `int`.
  - `toFloat(mixed)`: Normalizes commas to dots (`"19,90"` -> `19.90`) and casts to `float`.
  - `toBool(mixed)`: Recognizes truthy values (`'true'`, `'1'`, `'on'`, `'yes'`, `'s'`, `'sim'`, `1`) and falsy values (`'false'`, `'0'`, `'off'`, `'no'`, `'n'`, `'não'`, `'nao'`, `''`, `0`).
  - `toDateTimeImmutable(mixed, $format)`: Converts strings into `\DateTimeImmutable` instances using either standard formats or explicit custom format masks (`!Y-m-d`).

### 2.5. `ValidationContext` (`src/Core/ValidationContext.php`)
- **Role**: State carrier passed through every validation step.
- Tracks:
  - `path`: Current dotted path (e.g. `'shippingAddress.postalCode'`).
  - `rootData`: Full unmodified root input (useful for cross-field validations like `#[V\SameAs]`).
  - `errorBag`: Shared instance of `ErrorBag`.
  - `locale`: Optional per-validation locale override.
  - `testingMode`: Global static flag for testing uploads without HTTP `is_uploaded_file()` checks.

### 2.6. `ErrorBag` (`src/Core/ErrorBag.php`)
- **Role**: Accumulator for validation errors.
- Implements `\Countable`.
- Stores errors as `array<string, list<string>>` where keys are dotted paths (e.g. `'items.0.sku'`).
- Supports querying first error globally (`first()`) or per field (`first('email')`), and merging child bags with prefixes (`merge()`).

### 2.7. `UploadedFile` (`src/Files/UploadedFile.php`)
- **Role**: Secure wrapper for uploaded files.
- Inspects real MIME types via `finfo_open(FILEINFO_MIME_TYPE)` and `finfo_file()`, bypassing spoofed `$_FILES['...']['type']` values.
- Implements safe atomic moving via `moveTo($targetPath)` (creates target directories automatically if missing).
- Parses human-readable file sizes (`'5MB'`, `'500KB'`, `'1GB'`) into exact byte integers via `parseSizeToBytes()`.

### 2.8. `Translator` (`src/I18n/Translator.php`)
- **Role**: Singleton localization provider.
- Pre-loads catalogs for `pt-BR` and `en`.
- Interpolates placeholders (`:attribute`, `:min`, `:max`, `:format`, `:types`, `:values`, `:other`).
- Falls back to `pt-BR` if a message key is missing in the target locale.

---

## 3. Extensibility Architecture

Arbor Validator is designed to be easily extensible without modifying its core:
1. **Custom Rules**: Implement `Arbor\Validator\Rules\RuleInterface` (`validate(mixed $value, ValidationContext $context): bool`).
2. **Custom DTO Attributes**: Implement `Arbor\Validator\Attributes\ValidationAttributeInterface` and mark with `#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]`.
3. **Custom Schemas**: Extend `Arbor\Validator\Schemas\Schema` and implement `validateValue(mixed $value, ValidationContext $context): mixed`.
4. **Future MCP Layer Readiness**: All core validators and schemas accept pure associative arrays and return serializable `ValidationResult` objects with zero framework state, making it trivial to bind tools/resources in a future Model Context Protocol (MCP) server.
