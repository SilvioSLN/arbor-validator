# Quick Reference Cheat-Sheet: Arbor Validator

A fast lookup table of all schemas, attributes, and methods in **Arbor Validator**.

---

## 1. Schema Factory Methods (`AV::...`)

| Method | Returns | Description |
| :--- | :--- | :--- |
| `AV::string()` | `StringSchema` | String schema builder. |
| `AV::int()` | `IntSchema` | Integer schema builder. |
| `AV::float()` | `FloatSchema` | Float schema builder. |
| `AV::number()` | `NumberSchema` | General number schema builder (int/float). |
| `AV::bool()` / `AV::boolean()` | `BoolSchema` | Boolean schema builder. |
| `AV::shape(array $fields)` | `ShapeSchema` | Associative object/shape schema. |
| `AV::array(?Schema $item = null)`| `ArraySchema` | Array/list schema. |
| `AV::enum(array\|string $cases)` | `EnumSchema` | Enum schema (array or BackedEnum). |
| `AV::file()` | `FileSchema` | Uploaded file schema. |
| `AV::preprocess(callable, Schema)`| `PreprocessSchema` | Preprocessor pipeline schema. |
| `AV::coerce()` | `CoerceBuilder` | Coercion schema builder (`int`, `float`, `bool`, `date`). |

---

## 2. Common Schema Modifiers

| Method | Available On | Description |
| :--- | :--- | :--- |
| `optional()` | All schemas | Allows `null` or omission. |
| `nullable()` | All schemas | Allows `null`. |
| `default($val)` | All schemas | Sets default value when omitted/null. |
| `catch($val)` | All schemas | Fallback value on failure. |
| `transform(callable)`| All schemas | Modifies parsed value. |
| `refine(callable, msg)`| All schemas | Custom boolean check. |
| `superRefine(callable)`| All schemas | Custom context assertion. |
| `trim()` | `StringSchema` | Trims leading/trailing whitespace. |
| `min($val)` | `StringSchema`, `NumberSchema`, `ArraySchema` | Lower bound check. |
| `max($val)` | `StringSchema`, `NumberSchema`, `ArraySchema` | Upper bound check. |
| `email()` | `StringSchema` | Email address validation. |
| `cpf($stripMask)` | `StringSchema` | Brazilian CPF check. |
| `cnpj($alphanumeric, $stripMask)`| `StringSchema` | Traditional & 2024 RFB CNPJ check. |
| `phone($country, $stripMask)` | `StringSchema` | Brazilian & international phone check. |
| `fullName($minWords)` | `StringSchema` | Full name check. |
| `date($format)` | `StringSchema` | Date format check. |
| `time($format)` | `StringSchema` | Time format check. |
| `domain()` | `StringSchema` | Domain name check. |
| `url($protocols)` | `StringSchema` | URL check. |
| `uuid($version)` | `StringSchema` | UUID check. |
| `noHtml()` | `StringSchema` | Rejects HTML tags. |
| `html($sanitize)` | `StringSchema` | Validates/sanitizes HTML. |
| `emojis($allow, $only)` | `StringSchema` | Emoji presence control. |
| `stripMask()` | `StringSchema` | Removes punctuation/formatting. |
| `coerceDate($format)` | `StringSchema` | Converts to `\DateTimeImmutable`. |
| `sameAs($field, $other)` | `ShapeSchema` | Cross-field equality check. |
| `maxSize($size)` | `FileSchema` | Maximum file size (`'5MB'`, bytes). |
| `minSize($size)` | `FileSchema` | Minimum file size (`'10KB'`, bytes). |
| `extension($exts)` | `FileSchema` | Allowed extensions. |
| `mimeType($types)` | `FileSchema` | Allowed magic-bytes MIME types. |

---

## 3. DTO Attributes (`#[V\...]`)

| Attribute | Typical Usage | Notes |
| :--- | :--- | :--- |
| `#[V\Required]` | `#[V\Required]` | Field must be provided and not empty. |
| `#[V\Optional]` | `#[V\Optional]` | Field may be omitted. |
| `#[V\Nullable]` | `#[V\Nullable]` | Field may be `null`. |
| `#[V\Email]` | `#[V\Email]` | Standard email validation. |
| `#[V\Cpf]` | `#[V\Cpf(stripMask: true)]` | Validates CPF and strips punctuation. |
| `#[V\Cnpj]` | `#[V\Cnpj(allowAlphanumeric: true, stripMask: true)]` | RFB 2024 alphanumeric + traditional CNPJ. |
| `#[V\Phone]` | `#[V\Phone(country: 'BR', stripMask: true)]` | Official Brazilian DDD and mobile checks. |
| `#[V\FullName]` | `#[V\FullName(minWords: 2)]` | Requires at least 2 words. |
| `#[V\MinLength]` | `#[V\MinLength(8)]` | Minimum character length. |
| `#[V\MaxLength]` | `#[V\MaxLength(100)]` | Maximum character length. |
| `#[V\SameAs]` | `#[V\SameAs('password')]` | Matches another field in payload. |
| `#[V\Date]` | `#[V\Date(format: 'Y-m-d')]` | Coerces to `\DateTimeImmutable`. |
| `#[V\DateTime]` | `#[V\DateTime(format: 'Y-m-d H:i:s')]` | Validates datetime format. |
| `#[V\Time]` | `#[V\Time(format: 'H:i')]` | Validates 24-hour time. |
| `#[V\Url]` | `#[V\Url]` | Validates URL. |
| `#[V\Uuid]` | `#[V\Uuid]` | Validates UUID. |
| `#[V\Domain]` | `#[V\Domain]` | Validates domain. |
| `#[V\NoHtml]` | `#[V\NoHtml]` | Disallows HTML tags. |
| `#[V\Html]` | `#[V\Html(sanitize: true)]` | Validates/sanitizes HTML. |
| `#[V\Emojis]` | `#[V\Emojis(allow: false)]` | Disallows emojis. |
| `#[V\UploadedFile]` | `#[V\UploadedFile(maxSize: '5MB', extensions: ['jpg'])]` | Uploaded file validation. |
| `#[V\Nested]` | `#[V\Nested(AddressDTO::class)]` | Nested DTO object. |
| `#[V\Each]` | `#[V\Each(ItemDTO::class)]` | List of items or DTOs. |
| `#[V\Transform]` | `#[V\Transform('trim')]` | Applies transformer. |
| `#[V\Coerce]` | `#[V\Coerce]` | Explicit type coercion. |
