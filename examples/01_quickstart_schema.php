<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Validator\AV;

echo "=== Arbor Validator: 01 Quickstart Schema (Zod-like) ===\n\n";

// Define a fluent schema for a user profile
$profileSchema = AV::shape([
    'name'     => AV::string()->min(3)->max(100),
    'email'    => AV::string()->email()->transform(fn($val) => strtolower(trim($val))),
    'age'      => AV::coerce()->int()->min(18),
    'website'  => AV::string()->url()->optional(),
    'role'     => AV::enum(['admin', 'editor', 'viewer'])->default('viewer'),
    'active'   => AV::coerce()->bool()->default(true),
]);

// 1. Validating valid input
$validInput = [
    'name'    => 'Fulano da Silva',
    'email'   => '  user@example.COM ',
    'age'     => '28', // coerced from string to int
    'website' => 'https://github.com/silviosln',
];

$result = $profileSchema->safeParse($validInput);

echo "1. Valid input test:\n";
echo "   Is Valid: " . ($result->isValid() ? 'YES' : 'NO') . "\n";
echo "   Clean Data: " . json_encode($result->data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// 2. Validating invalid input
$invalidInput = [
    'name'    => 'Al', // too short (< 3)
    'email'   => 'not-an-email',
    'age'     => '15', // underage (< 18)
    'website' => 'not-a-url',
];

$failResult = $profileSchema->safeParse($invalidInput);

echo "2. Invalid input test:\n";
echo "   Failed: " . ($failResult->failed() ? 'YES' : 'NO') . "\n";
echo "   Errors found:\n";
foreach ($failResult->errors() as $field => $messages) {
    echo "     - {$field}: " . implode('; ', $messages) . "\n";
}

echo "\nCompleted successfully.\n";
