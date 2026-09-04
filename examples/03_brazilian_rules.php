<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Validator\AV;

echo "=== Arbor Validator: 03 Brazilian Validation Rules ===\n\n";

$brSchema = AV::shape([
    'full_name'        => AV::string()->fullName(minWords: 2),
    'cpf'              => AV::string()->cpf(stripMask: true),
    'cnpj_traditional' => AV::string()->cnpj(stripMask: true),
    'cnpj_2024_rfb'    => AV::string()->cnpj(allowAlphanumeric: true, stripMask: true),
    'mobile_sp'        => AV::string()->phone(country: 'BR', stripMask: true),
    'landline_rj'      => AV::string()->phone(country: 'BR', stripMask: true),
]);

// 1. Testing valid Brazilian data
$validData = [
    'full_name'        => 'Ana Maria de Souza',
    'cpf'              => '111.444.777-35',                 // Valid CPF
    'cnpj_traditional' => '11.222.333/0001-81',             // Traditional 14-digits numeric
    'cnpj_2024_rfb'    => '12.ABC.345/01DE-35',             // Official RFB 2024 Alphanumeric format
    'mobile_sp'        => '(11) 99876-5432',                // 11 digits (9th digit = 9)
    'landline_rj'      => '(21) 3234-5678',                 // 10 digits landline
];

$result = $brSchema->safeParse($validData);

echo "1. Valid Brazilian input validation:\n";
echo "   Is Valid: " . ($result->isValid() ? 'YES' : 'NO') . "\n";
echo "   Sanitized output (masks stripped):\n";
echo "   " . json_encode($result->data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// 2. Testing Brazilian edge cases & invalid values
$invalidData = [
    'full_name'        => 'ApenasPrimeiroNome',              // Fails: single word
    'cpf'              => '111.111.111-11',                 // Fails: repeated digits sequence
    'cnpj_traditional' => '12.ABC.345/01DE-35',             // Fails: alphanumeric not allowed when strict
    'cnpj_2024_rfb'    => '12.ABC.345/01DE-99',             // Fails: invalid Modulo 11 check digits
    'mobile_sp'        => '(11) 88765-4321',                // Fails: mobile missing initial '9'
    'landline_rj'      => '(00) 3234-5678',                 // Fails: DDD '00' is not a valid Brazilian area code
];

$strictCnpjSchema = AV::shape([
    'full_name'        => AV::string()->fullName(minWords: 2),
    'cpf'              => AV::string()->cpf(),
    'cnpj_traditional' => AV::string()->cnpj(allowAlphanumeric: false),
    'cnpj_2024_rfb'    => AV::string()->cnpj(allowAlphanumeric: true),
    'mobile_sp'        => AV::string()->phone(country: 'BR'),
    'landline_rj'      => AV::string()->phone(country: 'BR'),
]);

$failResult = $strictCnpjSchema->safeParse($invalidData);

echo "2. Rejection of invalid Brazilian data:\n";
foreach ($failResult->errors() as $field => $messages) {
    echo "   - {$field}: " . implode('; ', $messages) . "\n";
}

echo "\nCompleted successfully.\n";
