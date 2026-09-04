# Troubleshooting & Diagnostics: Arbor Validator

This guide provides solutions to common issues encountered during integration, testing, or usage.

---

## 1. File Upload Validation Fails in CLI, Tests, or Seeders

### Symptom
`UploadedFile::isValid()` returns `false`, or error `'file_invalid'` is returned when running tests in PHPUnit or CLI.

### Cause
PHP's native `is_uploaded_file()` function returns `false` unless the file was uploaded via an actual HTTP POST multipart request. Files created locally with `tempnam()` fail this check.

### Solution
Enable testing mode in your test's `setUp()` or bootstrap script:
```php
use Arbor\Validator\ArborValidator;

ArborValidator::setTestingMode(true);
```

---

## 2. Alphanumeric 2024 RFB CNPJs Failing Validation

### Symptom
A valid 2024 RFB alphanumeric CNPJ (such as `'12.ABC.345/01DE-35'`) is rejected with "CNPJ inválido".

### Cause
The schema or attribute was configured with `allowAlphanumeric: false` (restricting input to the legacy 14-digit numeric format).

### Solution
Enable alphanumeric support:
```php
// Schema:
AV::string()->cnpj(allowAlphanumeric: true);

// DTO Attribute:
#[V\Cnpj(allowAlphanumeric: true)]
public string $cnpj;
```

---

## 3. Decimal Numbers with Commas Failing Validation

### Symptom
Brazilian currency or quantity strings like `"1.250,50"` or `"19,90"` fail float validation.

### Cause
Standard `AV::float()` expects standard numeric notation (`19.90`).

### Solution
Use `AV::coerce()->float()` or apply `Coercer::toFloat()`:
```php
// Schema:
'price' => AV::coerce()->float()->positive();

// Or in DTO:
#[V\Required]
public float $price; // ClassMapper automatically coerces '19,90' to 19.90
```

---

## 4. `ValidationException` Thrown Unexpectedly When Accessing `$result->data()`

### Symptom
Fatal `ValidationException: Falha na validação...` occurs when calling `$result->data()`.

### Cause
Validation failed, and your code accessed `$result->data()` without checking `$result->isValid()`.

### Solution
Always use a guard clause before accessing `data()`:
```php
if ($result->failed()) {
    return Response::json(['errors' => $result->errors()], 422);
}

$dto = $result->data();
```

---

## 5. DTO Construction Fails with ArgumentCountError

### Symptom
`Erro ao instanciar DTO... Too few arguments to function...`

### Cause
A parameter without a default value was missing from the input, or had a validation failure that resulted in `null`.

### Solution
Ensure parameters that may be omitted are marked with `#[V\Optional]` and have default values:
```php
#[V\Optional]
public ?string $bio = null,
```
