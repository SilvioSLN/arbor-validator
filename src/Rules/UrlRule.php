<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class UrlRule implements RuleInterface
{
    /**
     * @param list<string> $protocols
     */
    public function __construct(
        public readonly array $protocols = ['http', 'https'],
        public readonly ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            $this->fail($context);
            return false;
        }

        if (!self::isValid($value, $this->protocols)) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    /**
     * @param list<string> $protocols
     */
    public static function isValid(string $url, array $protocols = ['http', 'https']): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme === null || $scheme === false) {
            return false;
        }

        return in_array(strtolower($scheme), array_map('strtolower', $protocols), true);
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('url');
        }
    }
}
