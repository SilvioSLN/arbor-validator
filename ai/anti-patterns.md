# Anti-Patterns: Arbor Validator

This document lists common mistakes, misconceptions, and anti-patterns to avoid when working with **Arbor Validator**.

---

## 1. Calling `$result->data()` Without Checking Validation Status

### ❌ WRONG
```php
$result = ArborValidator::validate(UserDTO::class, $input);
$dto = $result->data(); // Throws ValidationException if validation failed!
```

### ✅ CORRECT
```php
$result = ArborValidator::validate(UserDTO::class, $input);

if ($result->failed()) {
    return Response::json(['errors' => $result->errors()], 422);
}

$dto = $result->data(); // Safe to access
```

### Explanation
`ValidationResult::data()` is designed to protect your domain logic from consuming corrupted or unvalidated data. If `$result->failed()` is `true`, calling `data()` throws `ValidationException`. If you genuinely need the partial, unvalidated data, call `safeData()`.

---

## 2. Calling `$result->fails()` (Laravel Hallucination)

### ❌ WRONG
```php
if ($result->fails()) { // Fatal Error: Call to undefined method ValidationResult::fails()
    // ...
}
```

### ✅ CORRECT
```php
if ($result->failed()) {
    // ...
}
// or:
if (!$result->isValid()) {
    // ...
}
```

---

## 3. Trusting Client-Reported MIME Types

### ❌ WRONG
```php
// Trusting the file type from HTTP header or $_FILES['file']['type']:
if ($_FILES['file']['type'] === 'image/jpeg') {
    // Insecure! A malicious user can upload exploit.php with Content-Type: image/jpeg
}
```

### ✅ CORRECT
```php
AV::file()
    ->extension(['jpg', 'jpeg', 'png'])
    ->mimeType(['image/jpeg', 'image/png']); // Inactive of client headers; checks magic bytes!
```

---

## 4. Instantiating Internal Rules Directly in Controllers

### ❌ WRONG
```php
$rule = new \Arbor\Validator\Rules\CpfRule();
$isValid = $rule->validate($_POST['cpf'], new ValidationContext());
```

### ✅ CORRECT
```php
$result = ArborValidator::validate(['cpf' => AV::string()->cpf()], $_POST);
// or use DTO with #[V\Cpf]
```

### Explanation
Raw rule classes (`*Rule`) are internal validation primitives. Invoking them directly bypasses error localization, mask stripping, sanitization, and context aggregation.

---

## 5. Inventing Schema Types That Belong on `StringSchema`

### ❌ WRONG
```php
$schema = AV::shape([
    'email' => AV::email(), // Error: Call to undefined method AV::email()
    'uuid'  => AV::uuid(),  // Error: Call to undefined method AV::uuid()
    'cpf'   => AV::cpf(),   // Error: Call to undefined method AV::cpf()
]);
```

### ✅ CORRECT
```php
$schema = AV::shape([
    'email' => AV::string()->email(),
    'uuid'  => AV::string()->uuid(),
    'cpf'   => AV::string()->cpf(),
]);
```

### Explanation
Email, UUID, CPF, CNPJ, and Phone numbers are string formats. They are methods chained onto `AV::string()`.

---

## 6. Performing Database Queries Inside Validation Rules

### ❌ WRONG
```php
// Attempting to inject a PDO connection or ORM into a validation schema:
$schema = AV::shape([
    'email' => AV::string()->email()->refine(
        fn($email) => $db->query("SELECT id FROM users WHERE email = '$email'")->rowCount() === 0,
        "Email already registered"
    ),
]);
```

### ✅ CORRECT
```php
// Step 1: Arbor Validator validates syntax, format, and structure
$result = ArborValidator::validate(CreateUserDTO::class, $requestData);
if ($result->failed()) {
    return Response::json(['errors' => $result->errors()], 422);
}

// Step 2: Domain service validates uniqueness and stateful business rules
$dto = $result->data();
if ($this->userRepository->existsByEmail($dto->email)) {
    return Response::json(['errors' => ['email' => ['E-mail already in use.']]], 422);
}
```

### Explanation
Arbor Validator is deliberately zero-dependency and framework-agnostic. Keeping database queries in your domain/application services preserves separation of concerns, improves testability, and prevents slow validation loops.
