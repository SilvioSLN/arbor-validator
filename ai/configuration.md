# Configuration & Customization: Arbor Validator

This guide covers internationalization (i18n), testing mode, message catalog overrides, and extending Arbor Validator.

---

## 1. Internationalization (i18n)

Arbor Validator includes built-in translations for:
- `pt-BR` (default)
- `en` (English)

### 1.1. Changing the Global Locale
```php
use Arbor\Validator\ArborValidator;

// Switch global error messages to English
ArborValidator::setLocale('en');

// Switch back to Brazilian Portuguese
ArborValidator::setLocale('pt-BR');
```

### 1.2. Per-Validation Locale Override
If you build a multi-tenant or multi-language API where each request sends an `Accept-Language` header, you can pass the locale directly without changing global state:

```php
// With Class-First DTO:
$result = ArborValidator::validate(UserDTO::class, $data, locale: 'en');

// With Fluent Schema:
$result = $schema->safeParse($data, locale: 'en');
```

### 1.3. Overriding and Adding Custom Messages
You can customize existing messages or add new translation keys:

```php
use Arbor\Validator\ArborValidator;

ArborValidator::addMessages('pt-BR', [
    'email' => 'O endereço de e-mail informado (:attribute) não é aceito pela nossa empresa.',
    'cpf'   => 'CPF incorreto. Digite os 11 dígitos do titular.',
]);

ArborValidator::addMessages('en', [
    'email' => 'The provided corporate email (:attribute) is not authorized.',
]);
```

### 1.4. Available Placeholders in Messages
- `:attribute`: Name or dotted path of the field being validated.
- `:min`: Minimum bound (length, array count, number value).
- `:max`: Maximum bound (length, array count, number value).
- `:length`: Exact required length.
- `:format`: Expected date or time format (e.g. `Y-m-d`).
- `:target` / `:other`: Target field name for cross-field comparisons (`#[V\SameAs]`).
- `:extensions`: Comma-separated list of allowed file extensions.
- `:types`: Comma-separated list of allowed MIME types.
- `:values`: Comma-separated list of allowed enum values.

---

## 2. Testing Mode (`setTestingMode`)

### Why Testing Mode Exists
In PHP, the native function `is_uploaded_file($path)` and `move_uploaded_file($from, $to)` return `false` unless the file was uploaded via an actual HTTP POST multipart request.

When writing unit tests (PHPUnit), CLI commands, or database seeders, you typically create mock files in `/tmp` using `tempnam()` or `file_put_contents()`. Without testing mode, `UploadedFile::isValid()` would reject them.

### Enabling Testing Mode
```php
use Arbor\Validator\ArborValidator;

// In your TestCase::setUp() or bootstrap script:
ArborValidator::setTestingMode(true);
```

When enabled:
- `UploadedFile::isValid()` validates that the file exists and has size > 0, bypassing `is_uploaded_file()`.
- `UploadedFile::moveTo()` uses `rename()` instead of `move_uploaded_file()`.
- Server magic-byte MIME type inspection (`finfo_file`) continues to operate normally.

---

## 3. Extending the Library

### 3.1. Custom Attribute with Sanitization
You can create custom attributes that not only validate but also mutate/clean the value before it reaches the DTO constructor:

```php
namespace App\Attributes;

use Arbor\Validator\Attributes\ValidationAttributeInterface;
use Arbor\Validator\Core\ValidationContext;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class CleanSku implements ValidationAttributeInterface
{
    public function validate(mixed $value, ValidationContext $context): bool
    {
        if (!is_string($value) || !preg_match('/^[A-Z0-9\-]+$/i', $value)) {
            $context->addError("O SKU deve conter apenas letras, números e hífens.");
            return false;
        }

        return true;
    }

    /**
     * Optional method called by ClassMapper upon validation success.
     */
    public function sanitize(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }
}
```
