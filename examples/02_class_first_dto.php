<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Validator\ArborValidator;
use Arbor\Validator\Attributes as V;

echo "=== Arbor Validator: 02 Class-First DTO Validation ===\n\n";

// Define a strongly typed DTO class using PHP 8 Attributes
final readonly class CreateUserDTO
{
    public function __construct(
        #[V\Required, V\FullName]
        public string $name,

        #[V\Required, V\Email]
        public string $email,

        #[V\Required, V\MinLength(8)]
        public string $password,

        #[V\Required, V\SameAs('password', message: 'As senhas não coincidem')]
        public string $passwordConfirmation,

        #[V\Required, V\Cpf(stripMask: true)]
        public string $cpf,

        #[V\Required, V\Phone(country: 'BR', stripMask: true)]
        public string $phone,

        #[V\Optional, V\Date(format: 'Y-m-d')]
        public ?\DateTimeImmutable $birthDate = null,
    ) {
    }
}

// 1. Simulating HTTP request payload (with snake_case keys)
$requestPayload = [
    'name'                  => 'Carlos Eduardo da Silva',
    'email'                 => 'carlos.silva@empresa.com.br',
    'password'              => 'segredo123',
    'password_confirmation' => 'segredo123',
    'cpf'                   => '111.444.777-35',        // Valid CPF with formatting
    'phone'                 => '(11) 98765-4321',       // Valid SP cellphone
    'birth_date'            => '1995-04-12',           // Automatically converted to \DateTimeImmutable
];

// Validate using the DTO class
$result = ArborValidator::validate(CreateUserDTO::class, $requestPayload);

if ($result->isValid()) {
    /** @var CreateUserDTO $dto */
    $dto = $result->data();

    echo "1. DTO successfully mapped and validated:\n";
    echo "   Class: " . get_class($dto) . "\n";
    echo "   Name: {$dto->name}\n";
    echo "   Email: {$dto->email}\n";
    echo "   CPF (mask stripped): {$dto->cpf}\n";
    echo "   Phone (mask stripped): {$dto->phone}\n";
    echo "   Birth Date: " . $dto->birthDate?->format('d/m/Y') . " (Instance of " . get_class($dto->birthDate) . ")\n";
} else {
    echo "1. Validation failed unexpectedly:\n";
    print_r($result->errors());
}

// 2. Demonstration of validation failure with wrong CPF and mismatching password
$invalidPayload = [
    'name'                  => 'Carlos', // only 1 word, FullName requires at least 2
    'email'                 => 'invalido',
    'password'              => '12345678',
    'password_confirmation' => 'diferente',
    'cpf'                   => '123.456.789-00', // Invalid checksum
    'phone'                 => '0000-0000',      // Invalid DDD and format
];

$failResult = ArborValidator::validate(CreateUserDTO::class, $invalidPayload);

echo "\n2. Invalid input test errors:\n";
foreach ($failResult->errors() as $field => $errors) {
    echo "   - {$field}: " . implode('; ', $errors) . "\n";
}

echo "\nCompleted successfully.\n";
