# Error Handling & Message Dictionaries: Arbor Validator

This document explains error structures, dotted path representations, exception handling, and the complete dictionary of translation keys.

---

## 1. Error Representation Structure

Errors in Arbor Validator are represented as an associative array where:
- **Keys** are field names or hierarchical dotted paths.
- **Values** are lists of human-readable error strings.

### Structure Example
```json
{
  "email": [
    "O campo email deve ser um endereço de e-mail válido."
  ],
  "address.zipCode": [
    "O campo address.zipCode é obrigatório."
  ],
  "items.0.price": [
    "O campo items.0.price deve ser um número decimal."
  ]
}
```

---

## 2. Inspecting Errors on `ValidationResult`

When using safe mode (`ArborValidator::validate()` or `$schema->safeParse()`):

```php
$result = ArborValidator::validate(UserDTO::class, $data);

if ($result->failed()) {
    // 1. All errors map
    $allErrors = $result->errors(); // array<string, list<string>>

    // 2. First error anywhere in the payload
    $first = $result->firstError(); // ?string

    // 3. First error of a specific field
    $emailErr = $result->error('email'); // ?string

    // 4. All errors of a specific field
    $emailErrs = $result->fieldErrors('email'); // list<string>

    // 5. Checking if a specific field has errors
    if ($result->hasError('cpf')) {
        // ...
    }
}
```

---

## 3. Exception Mode (`ValidationException`)

When using exception mode (`ArborValidator::parse()` or `$schema->parse()`):

```php
use Arbor\Validator\ArborValidator;
use Arbor\Validator\Exceptions\ValidationException;

try {
    $dto = ArborValidator::parse(UserDTO::class, $data);
} catch (ValidationException $e) {
    $httpStatusCode = $e->getCode(); // 422
    $firstError = $e->firstError();   // string
    $errorsMap = $e->errors();        // array<string, list<string>>
    $specific = $e->error('email');   // ?string
}
```

---

## 4. Complete Translation Catalog

| Key | Portuguese (`pt-BR`) Template | English (`en`) Template |
| :--- | :--- | :--- |
| `required` | O campo :attribute é obrigatório. | The :attribute field is required. |
| `min_length` | O campo :attribute deve conter no mínimo :min caracteres. | The :attribute must be at least :min characters. |
| `max_length` | O campo :attribute deve conter no máximo :max caracteres. | The :attribute may not be greater than :max characters. |
| `length` | O campo :attribute deve conter exatamente :length caracteres. | The :attribute must be exactly :length characters. |
| `regex` | O formato do campo :attribute é inválido. | The :attribute format is invalid. |
| `email` | O campo :attribute deve ser um endereço de e-mail válido. | The :attribute field must be a valid email address. |
| `cpf` | O campo :attribute contém um CPF inválido. | The :attribute field contains an invalid CPF. |
| `cnpj` | O campo :attribute contém um CNPJ inválido. | The :attribute field contains an invalid CNPJ. |
| `phone` | O campo :attribute contém um número de telefone inválido. | The :attribute field contains an invalid phone number. |
| `full_name` | O campo :attribute deve conter o nome completo (ao menos duas palavras). | The :attribute field must contain a full name (at least two words). |
| `date` | O campo :attribute deve ser uma data válida no formato :format. | The :attribute must be a valid date in format :format. |
| `time` | O campo :attribute deve ser um horário válido no formato :format. | The :attribute must be a valid time in format :format. |
| `datetime` | O campo :attribute deve ser uma data e hora válidas no formato :format. | The :attribute must be a valid datetime in format :format. |
| `domain` | O campo :attribute deve ser um nome de domínio válido. | The :attribute field must be a valid domain name. |
| `url` | O campo :attribute deve ser uma URL válida. | The :attribute field must be a valid URL. |
| `uuid` | O campo :attribute deve ser um UUID válido. | The :attribute field must be a valid UUID. |
| `no_html` | O campo :attribute não pode conter tags HTML ou scripts. | The :attribute field cannot contain HTML or script tags. |
| `html` | O campo :attribute deve ser um HTML válido. | The :attribute field must be valid HTML. |
| `emojis` | O campo :attribute não permite o uso de emojis. | The :attribute field does not allow emojis. |
| `only_emojis` | O campo :attribute deve conter apenas emojis. | The :attribute field must contain only emojis. |
| `same_as` | O campo :attribute deve ser idêntico ao campo :other. | The :attribute field must match :other. |
| `file_required` | O envio do arquivo :attribute é obrigatório. | The :attribute file is required. |
| `file_invalid` | O arquivo enviado para :attribute é inválido ou corrompido. | The uploaded file for :attribute is invalid. |
| `file_max_size` | O arquivo :attribute excede o tamanho máximo de :max. | The file :attribute exceeds the maximum size of :max. |
| `file_min_size` | O arquivo :attribute é menor que o tamanho mínimo de :min. | The file :attribute is smaller than the minimum size of :min. |
| `file_extension` | O arquivo :attribute deve ter uma das extensões: :extensions. | The file :attribute must have one of the extensions: :extensions. |
| `file_mime_type` | O tipo do arquivo :attribute é inválido (:mime). Tipos permitidos: :types. | The file type for :attribute is invalid (:mime). Allowed: :types. |
| `number` | O campo :attribute deve ser um número. | The :attribute field must be a number. |
| `integer` | O campo :attribute deve ser um número inteiro. | The :attribute field must be an integer. |
| `float` | O campo :attribute deve ser um número decimal. | The :attribute field must be a float. |
| `positive` | O campo :attribute deve ser maior que zero. | The :attribute field must be greater than zero. |
| `negative` | O campo :attribute deve ser menor que zero. | The :attribute field must be less than zero. |
| `min_value` | O campo :attribute deve ser no mínimo :min. | The :attribute must be at least :min. |
| `max_value` | O campo :attribute deve ser no máximo :max. | The :attribute may not be greater than :max. |
| `boolean` | O campo :attribute deve ser verdadeiro ou falso. | The :attribute field must be true or false. |
| `enum` | O valor selecionado para :attribute é inválido. Opções permitidas: :values. | The selected value for :attribute is invalid. Allowed: :values. |
| `array` | O campo :attribute deve ser um array. | The :attribute field must be an array. |
| `array_min` | O campo :attribute deve conter ao menos :min itens. | The :attribute must contain at least :min items. |
| `array_max` | O campo :attribute não pode conter mais de :max itens. | The :attribute may not contain more than :max items. |
