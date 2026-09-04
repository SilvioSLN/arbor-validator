<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;

class EmojisRule implements RuleInterface
{
    public const string EMOJI_REGEX = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}\x{200D}]/u';

    public function __construct(
        public readonly bool $allow = true,
        public readonly bool $only = false,
        public readonly ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_scalar($value)) {
            $this->failNotAllowed($context);
            return false;
        }

        $str = (string) $value;
        $hasEmojis = (bool) preg_match(self::EMOJI_REGEX, $str);

        if (!$this->allow && $hasEmojis) {
            $this->failNotAllowed($context);
            return false;
        }

        if ($this->only) {
            // Remove todos os emojis e espaços; se sobrar algo, não é somente emojis
            $stripped = preg_replace(self::EMOJI_REGEX, '', $str);
            $stripped = preg_replace('/\s+/u', '', (string) $stripped);
            if ($stripped !== '' || !$hasEmojis) {
                $this->failOnly($context);
                return false;
            }
        }

        return true;
    }

    private function failNotAllowed(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('emojis');
        }
    }

    private function failOnly(ValidationContext $context): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey('only_emojis');
        }
    }
}
