<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class FullNameRule implements RuleInterface
{
    public function __construct(
        public readonly int $minWords = 2,
        public readonly int $minWordLength = 2,
        public readonly ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_scalar($value)) {
            $this->fail($context);
            return false;
        }

        $str = trim((string) $value);
        if (!self::isValid($str, $this->minWords, $this->minWordLength)) {
            $this->fail($context);
            return false;
        }

        return true;
    }

    public static function isValid(string $name, int $minWords = 2, int $minWordLength = 2): bool
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return false;
        }

        // Divide por espaços múltiplos
        $words = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || count($words) < $minWords) {
            return false;
        }

        // Verifica se cada palavra atinge o tamanho mínimo (suportando UTF-8)
        foreach ($words as $word) {
            // Se a palavra terminar com ponto (ex: 'S.'), é uma abreviação
            if (str_ends_with($word, '.')) {
                return false;
            }

            $cleanWord = trim($word, " \t\n\r\0\x0B.,;:-_");
            if (mb_strlen($cleanWord) < $minWordLength) {
                return false;
            }
        }

        return true;
    }

    private function fail(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('full_name');
        }
    }
}
