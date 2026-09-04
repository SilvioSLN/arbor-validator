<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Validator\ArborValidator;
use Arbor\Validator\AV;
use Arbor\Validator\Exceptions\ValidationException;

echo "=== Arbor Validator: 06 Error Handling, Exceptions and i18n ===\n\n";

$schema = AV::shape([
    'email' => AV::string()->email(),
    'age'   => AV::int()->min(18),
]);

$invalidPayload = [
    'email' => 'invalid-email',
    'age'   => 12,
];

// 1. Inspecting ValidationResult methods in Portuguese (default)
echo "1. Inspecting ValidationResult in default locale (pt-BR):\n";
$result = $schema->safeParse($invalidPayload);

echo "   isValid(): " . ($result->isValid() ? 'true' : 'false') . "\n";
echo "   failed(): " . ($result->failed() ? 'true' : 'false') . "\n";
echo "   hasError('email'): " . ($result->hasError('email') ? 'true' : 'false') . "\n";
echo "   error('email'): " . $result->error('email') . "\n";
echo "   firstError(): " . $result->firstError() . "\n\n";

// 2. Switching to English locale
echo "2. Switching global locale to English ('en'):\n";
ArborValidator::setLocale('en');

$enResult = $schema->safeParse($invalidPayload);
echo "   English firstError(): " . $enResult->firstError() . "\n";
echo "   English email error: " . $enResult->error('email') . "\n";
echo "   English age error: " . $enResult->error('age') . "\n\n";

// 3. Adding custom translation messages
echo "3. Adding custom messages for a locale:\n";
ArborValidator::addMessages('pt-BR', [
    'email' => 'Por favor, forneça um correio eletrônico corporativo válido.',
]);
ArborValidator::setLocale('pt-BR');

$customResult = $schema->safeParse($invalidPayload);
echo "   Custom pt-BR email error: " . $customResult->error('email') . "\n\n";

// 4. Exception mode using ArborValidator::parse()
echo "4. Exception handling using parse():\n";
try {
    ArborValidator::parse($schema, $invalidPayload);
} catch (ValidationException $e) {
    echo "   Caught ValidationException!\n";
    echo "   HTTP Status Code: " . $e->getCode() . "\n";
    echo "   Exception Message: " . $e->getMessage() . "\n";
    echo "   JSON serialized errors: " . json_encode($e->errors()) . "\n";
}

echo "\nCompleted successfully.\n";
