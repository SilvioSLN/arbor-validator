# 🌳 Arbor Validator

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](tests/)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-8892BF.svg)](composer.json)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Dependencies](https://img.shields.io/badge/dependencies-0-success.svg)](composer.json)
[![AI Ready](https://img.shields.io/badge/AI-Ready-8A2BE2.svg)](AGENTS.md)

*Disponível em: [English](README.en.md) | Guia para Agentes de IA: [AGENTS.md](AGENTS.md)*

**Arbor Validator** é uma biblioteca de validação moderna para PHP 8.3+, agnóstica de framework e com **zero dependências externas**. Inspirada na Developer Experience (DX) do **Zod** (TypeScript) e nas capacidades nativas de tipagem, reflexão e atributos do PHP moderno.

Ela resolve simultaneamente quatro desafios essenciais no recebimento de dados:
1. **Validação Rigorosa:** Regras estritas, incluindo validações brasileiras especializadas (algoritmo oficial de CPF e o **novo padrão CNPJ Alfanumérico da Receita Federal**).
2. **Validação Segura de Arquivos / Uploads:** Validação real de arquivos (`$_FILES`) checando tamanho, extensão e **MIME type real (magic bytes)** via `finfo_file` para impedir spoofing de extensões.
3. **Coerção e Transformação:** Coerção inteligente para formulários HTTP e pipelines de transformação com `.transform()` e `.preprocess()`.
4. **Mapeamento de Saída:** Devolve dados 100% seguros e tipados, seja como um **DTO (Classe Tipada)** ou como um **Array Sanitizado**.

---

## 📦 Instalação

```bash
composer require silviosln/arbor-validator
```

### Requisitos
* **PHP 8.3** ou superior (`declare(strict_types=1);`, readonly classes, attributes)
* Extensões nativas: `ext-mbstring`, `ext-json`, `ext-fileinfo`
* **Zero dependências externas** em produção.

---

## 🛠 As Duas Abordagens Nativas

O Arbor Validator suporta duas formas de validação compartilhando o mesmo motor interno de validação e coerção:

### 1. Class-First (DTOs com PHP 8 Attributes)
Ideal para payloads complexos, APIs REST com tipagem forte e Domain-Driven Design (DDD). A própria classe PHP é o contrato/schema.

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

        #[V\Required, V\SameAs('password', message: 'A confirmação de senha não confere')]
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

// Execução no Controller / Endpoint:
$result = ArborValidator::validate(RegisterUserDTO::class, array_merge($_POST, $_FILES));

if ($result->failed()) {
    // Retorna mapa de erros: ['cpf' => ['O campo cpf contém um CPF inválido.'], ...]
    return response()->json(['errors' => $result->errors()], 422);
}

/** @var RegisterUserDTO $dto */
$dto = $result->data(); // Instância pronta, validada e estritamente tipada
echo $dto->name;
echo $dto->cpf; // '11144477735' (sanitizado com stripMask)
echo $dto->birthDate->format('d/m/Y'); // \DateTimeImmutable
```

---

### 2. Schema / Array Fluent (Estilo Zod)
Ideal para validações pontuais, formulários rápidos e rotas sem a necessidade de criar arquivos de classe DTO.

```php
use Arbor\Validator\AV;

$registerSchema = AV::shape([
    'name'     => AV::string()->fullName()->min(3)->max(100),
    'email'    => AV::string()->email()->transform(fn($e) => strtolower(trim($e))),
    'password' => AV::string()->min(8),
    'password_confirmation' => AV::string(),
    'cpf'      => AV::string()->cpf(stripMask: true),
    'cnpj'     => AV::string()->cnpj(allowAlphanumeric: true, stripMask: true)->optional(),
    'phone'    => AV::string()->phone('BR', stripMask: true),
    'domain'   => AV::string()->domain()->optional(),
    'url'      => AV::string()->url()->optional(),
    'time'     => AV::string()->time('H:i')->optional(),
    'date'     => AV::string()->date('Y-m-d')->coerceDate(), // Converte para \DateTimeImmutable
    'avatar'   => AV::file()
                    ->maxSize('5MB')
                    ->extension(['jpg', 'png', 'webp'])
                    ->mimeType(['image/jpeg', 'image/png', 'image/webp'])
                    ->optional(),
])
// Confirmação de senha
->sameAs('password_confirmation', 'password', 'A confirmação de senha não coincide');

// Validação segura (não lança exceção)
$result = $registerSchema->safeParse(array_merge($_POST, $_FILES));

if (!$result->success) {
    return response()->json(['errors' => $result->errors()], 422);
}

$cleanData = $result->data(); // array com dados sanitizados e convertidos
```

---

## 🇧🇷 Validações Especializadas Brasileiras

### 1. CPF (`AV::string()->cpf()` / `#[V\Cpf]`)
* Validação oficial por algoritmo de dois dígitos verificadores (Módulo 11).
* Rejeita automaticamente sequências repetidas (`111.111.111-11`, `000.000.000-00`, etc.).
* Aceita dados com máscara (`123.456.789-00`) ou sem máscara (`12345678900`).
* Suporte nativo a `stripMask: true` para salvar apenas dígitos no banco.

### 2. Novo CNPJ Alfanumérico da Receita Federal (`AV::string()->cnpj()` / `#[V\Cnpj]`)
* Totalmente compatível com o formato tradicional numérico (14 dígitos) e com a **Normativa RFB nº 2.229/2024** (novo padrão alfanumérico).
* Posições 1 a 12 alfanuméricas (`0-9` e `A-Z`) e posições 13 e 14 com dígitos verificadores numéricos.
* Cálculo ponderado ASCII oficial (`ord(char) - 48`) com Módulo 11.
* Suporte a `allowAlphanumeric: false` caso queira aceitar estritamente o formato legado numérico.

### 3. Telefone Brasileiro e Internacional (`AV::string()->phone()` / `#[V\Phone]`)
* Valida celulares (11 dígitos com 9º dígito obrigatório `'9'`) e telefones fixos (10 dígitos com dígitos iniciais válidos `2`, `3`, `4` ou `5`).
* Valida lista completa de DDDs oficiais do Brasil (evita DDDs inválidos como `00`, `01`, `20`, etc.).
* Suporte a formato internacional E.164 (`+5511999998888`) e remoção automática de máscara (`stripMask: true`).

---

## 📁 Validação Real de Uploads (`$_FILES`)

Muitas bibliotecas confiam cegamente em `$_FILES['avatar']['type']`, que pode ser facilmente falsificado pelo cliente (ex: um script `.php` disfarçado de `.jpg`).

O **Arbor Validator** lê os **bytes mágicos reais** do arquivo no servidor através do `finfo_file()`:

```php
AV::file()
    ->maxSize('5MB') // Converte strings legíveis ('500KB', '2GB') em bytes reais
    ->minSize('10KB')
    ->extension(['jpg', 'png', 'pdf'])
    ->mimeType(['image/jpeg', 'image/png', 'application/pdf']);
```

O arquivo validado pode ser manipulado através do objeto `UploadedFile`:
```php
$file = $result->data()['avatar']; // Instância de Arbor\Validator\Files\UploadedFile

echo $file->clientName();     // "foto.jpg"
echo $file->mimeType();       // "image/jpeg" (inspecionado via finfo)
echo $file->extension();      // "jpg"
echo $file->size();           // 204800 (bytes)

// Move o arquivo com segurança para o destino
$file->moveTo('/var/www/uploads/avatar_123.jpg');
```

---

## 🧱 DTOs Aninhados e Listas de Itens (`#[V\Nested]`, `#[V\Each]`)

O Arbor Validator suporta estruturas complexas e listas aninhadas, mapeando erros hierárquicos em **notação pontilhada** (`address.street`, `items.0.price`):

```php
use Arbor\Validator\Attributes as V;

final readonly class OrderDTO
{
    public function __construct(
        #[V\Required]
        public string $customer,

        #[V\Required, V\Nested(AddressDTO::class)]
        public AddressDTO $address,

        #[V\Required, V\Each(OrderItemDTO::class)]
        public array $items,
    ) {}
}

$result = ArborValidator::validate(OrderDTO::class, $requestData);

if ($result->failed()) {
    // Erros pontilhados:
    // [
    //     'address.street' => ['O campo address.street é obrigatório.'],
    //     'items.0.price'  => ['O campo items.0.price deve ser um número.']
    // ]
    return response()->json(['errors' => $result->errors()], 422);
}
```

---

## 🛡 Objeto `ValidationResult` (API First-Class)

```php
$result = ArborValidator::validate($dtoOrSchema, $data);

$result->isValid();               // bool (true se passou)
$result->failed();                // bool (true se falhou)
$result->data();                  // Retorna DTO ou array limpo (lança ValidationException se falhou)
$result->safeData();              // Retorna dados sem lançar exceção
$result->errors();                // array<string, list<string>> com todos os erros
$result->firstError();            // ?string com o primeiro erro global
$result->error('cpf');            // ?string com o primeiro erro do campo
$result->fieldErrors('cpf');      // list<string> com todos os erros do campo
$result->hasError('cpf');         // bool
```

---

## ⚡ Modo Seguro (`safeParse`) vs Exceção (`parse`)

Se preferir utilizar blocos `try/catch` em middlewares ou handlers de API:

```php
use Arbor\Validator\ArborValidator;
use Arbor\Validator\Exceptions\ValidationException;

try {
    $dto = ArborValidator::parse(RegisterUserDTO::class, $_POST);
    // Dados 100% válidos...
} catch (ValidationException $e) {
    return response()->json([
        'message' => $e->getMessage(),
        'errors'  => $e->errors(),
    ], 422);
}
```

---

## 🌐 Internacionalização e Idiomas (i18n)

Por padrão, todas as mensagens são exibidas em **Português (pt-BR)**. Você pode alterar o idioma global para Inglês ou cadastrar novas mensagens customizadas:

```php
use Arbor\Validator\ArborValidator;

// Altera o idioma global para Inglês
ArborValidator::setLocale('en');

// Adiciona ou sobrescreve mensagens personalizadas
ArborValidator::addMessages('pt-BR', [
    'cpf' => 'O CPF informado não é válido perante a Receita Federal.',
]);
```

---

## 🔌 Integração Nativa com Arbor Router

O Arbor Validator fornece a trait `ValidatesRequestTrait`, pronta para ser adicionada no objeto `Request` do Arbor Router (ou em qualquer aplicação):

```php
use Arbor\Validator\Integration\ValidatesRequestTrait;

class Request
{
    use ValidatesRequestTrait;
}

// Dentro do seu Action/Controller do Arbor Router:
return function(Request $request) {
    $result = $request->validate(UpdateProfileDTO::class);

    if ($result->failed()) {
        return ActionResult::error($result->errors());
    }

    $dto = $result->data();
    // Execução segura...
};
```

---

## 🧪 Testes e Qualidade

O projeto conta com suíte de testes completa em **PHPUnit 11** e análise estática avançada com **PHPStan**:

```bash
# Executar testes unitários e de integração
composer test

# Executar testes e gerar relatório de cobertura em HTML
composer test:coverage

# Executar análise estática de tipos (Level 6)
composer phpstan
```

---

## 📄 Licença

Distribuído sob a licença MIT. Veja `LICENSE` para mais detalhes.
