<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Validator\Attributes as V;
use Arbor\Validator\Exceptions\ValidationException;
use Arbor\Validator\Integration\ValidatesRequestTrait;

echo "=== Arbor Validator: 07 Request Integration (ValidatesRequestTrait) ===\n\n";

// A simulated Request class (like in Arbor Router, Slim, or custom MVC)
class HttpRequest
{
    use ValidatesRequestTrait;

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files
     */
    public function __construct(
        private readonly array $body = [],
        private readonly array $files = [],
    ) {
    }

    /**
     * Simulated parsed body method (Arbor Router / PSR-7)
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->body;
    }

    /**
     * Simulated uploaded files method
     *
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }
}

// Define the action DTO
final readonly class UpdateSettingsDTO
{
    public function __construct(
        #[V\Required]
        public string $theme,

        #[V\Required]
        public bool $notificationsEnabled,
    ) {
    }
}

// 1. Controller action with safe validation ($request->validate())
$request = new HttpRequest(body: [
    'theme'                 => 'dark',
    'notifications_enabled' => 'true', // Auto coerced to boolean
]);

$result = $request->validate(UpdateSettingsDTO::class);

echo "1. Safe Controller validation:\n";
if ($result->isValid()) {
    /** @var UpdateSettingsDTO $dto */
    $dto = $result->data();
    echo "   Settings updated successfully:\n";
    echo "   Theme: {$dto->theme}\n";
    echo "   Notifications: " . ($dto->notificationsEnabled ? 'ON' : 'OFF') . "\n";
} else {
    echo "   Failed with errors: " . json_encode($result->errors()) . "\n";
}

// 2. Controller action with exception validation ($request->validateOrFail())
echo "\n2. Exception-based Controller validation (validateOrFail):\n";
$badRequest = new HttpRequest(body: [
    'theme' => '', // missing theme
]);

try {
    $badRequest->validateOrFail(UpdateSettingsDTO::class);
} catch (ValidationException $e) {
    echo "   Intercepted ValidationException:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   HTTP Response code: " . $e->getCode() . "\n";
    echo "   Errors for JSON response:\n";
    echo "   " . json_encode($e->errors(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

echo "\nCompleted successfully.\n";
