<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\CnpjRule;
use Arbor\Validator\Rules\CpfRule;
use Arbor\Validator\Rules\DateRule;
use Arbor\Validator\Rules\DomainRule;
use Arbor\Validator\Rules\EmailRule;
use Arbor\Validator\Rules\EmojisRule;
use Arbor\Validator\Rules\FullNameRule;
use Arbor\Validator\Rules\HtmlRule;
use Arbor\Validator\Rules\NoHtmlRule;
use Arbor\Validator\Rules\PhoneRule;
use Arbor\Validator\Rules\RuleInterface;
use Arbor\Validator\Rules\TimeRule;
use Arbor\Validator\Rules\UrlRule;
use Arbor\Validator\Rules\UuidRule;

class StringSchema extends Schema
{
    /**
     * @var list<RuleInterface>
     */
    protected array $rules = [];

    protected bool $shouldTrim = false;
    protected ?int $min = null;
    protected ?string $minMessage = null;
    protected ?int $max = null;
    protected ?string $maxMessage = null;
    protected ?int $exactLength = null;
    protected ?string $exactLengthMessage = null;
    protected ?string $regexPattern = null;
    protected ?string $regexMessage = null;

    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            $context->addErrorByKey('regex'); // Formato inválido
            return $value;
        }

        $str = (string) $value;
        if ($this->shouldTrim) {
            $str = trim($str);
        }

        $len = mb_strlen($str);

        if ($this->exactLength !== null && $len !== $this->exactLength) {
            if ($this->exactLengthMessage !== null) {
                $context->addError($this->exactLengthMessage);
            } else {
                $context->addErrorByKey('length', ['length' => $this->exactLength]);
            }
        }

        if ($this->min !== null && $len < $this->min) {
            if ($this->minMessage !== null) {
                $context->addError($this->minMessage);
            } else {
                $context->addErrorByKey('min_length', ['min' => $this->min]);
            }
        }

        if ($this->max !== null && $len > $this->max) {
            if ($this->maxMessage !== null) {
                $context->addError($this->maxMessage);
            } else {
                $context->addErrorByKey('max_length', ['max' => $this->max]);
            }
        }

        if ($this->regexPattern !== null && !preg_match($this->regexPattern, $str)) {
            if ($this->regexMessage !== null) {
                $context->addError($this->regexMessage);
            } else {
                $context->addErrorByKey('regex');
            }
        }

        foreach ($this->rules as $rule) {
            if (!$rule->validate($str, $context)) {
                // Erro adicionado pela regra
                continue;
            }

            if (method_exists($rule, 'sanitize')) {
                $str = (string) $rule->sanitize($str);
            }
        }

        return $str;
    }

    public function trim(): static
    {
        $clone = clone $this;
        $clone->shouldTrim = true;
        return $clone;
    }

    public function min(int $min, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->min = $min;
        $clone->minMessage = $message;
        return $clone;
    }

    public function max(int $max, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->max = $max;
        $clone->maxMessage = $message;
        return $clone;
    }

    public function length(int $length, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->exactLength = $length;
        $clone->exactLengthMessage = $message;
        return $clone;
    }

    public function regex(string $pattern, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->regexPattern = $pattern;
        $clone->regexMessage = $message;
        return $clone;
    }

    public function email(bool $checkDns = false, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new EmailRule($checkDns, $message);
        return $clone;
    }

    public function cpf(bool $stripMask = false, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new CpfRule($stripMask, $message);
        return $clone;
    }

    public function cnpj(bool $allowAlphanumeric = true, bool $stripMask = false, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new CnpjRule($allowAlphanumeric, $stripMask, $message);
        return $clone;
    }

    public function phone(string $country = 'BR', bool $stripMask = false, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new PhoneRule($country, $stripMask, $message);
        return $clone;
    }

    public function fullName(int $minWords = 2, int $minWordLength = 2, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new FullNameRule($minWords, $minWordLength, $message);
        return $clone;
    }

    public function date(string $format = 'Y-m-d', ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new DateRule($format, $message);
        return $clone;
    }

    public function time(string $format = 'H:i', ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new TimeRule($format, $message);
        return $clone;
    }

    public function domain(?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new DomainRule($message);
        return $clone;
    }

    /**
     * @param list<string> $protocols
     */
    public function url(array $protocols = ['http', 'https'], ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new UrlRule($protocols, $message);
        return $clone;
    }

    public function uuid(int $version = 0, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new UuidRule($version, $message);
        return $clone;
    }

    public function noHtml(?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new NoHtmlRule($message);
        return $clone;
    }

    public function html(bool $sanitize = false, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new HtmlRule($sanitize, $message);
        return $clone;
    }

    public function emojis(bool $allow = true, bool $only = false, ?string $message = null): static
    {
        $clone = clone $this;
        $clone->rules[] = new EmojisRule($allow, $only, $message);
        return $clone;
    }

    public function lowercase(): static
    {
        return $this->transform(fn($val) => is_string($val) ? mb_strtolower($val) : $val);
    }

    public function uppercase(): static
    {
        return $this->transform(fn($val) => is_string($val) ? mb_strtoupper($val) : $val);
    }

    public function stripMask(): static
    {
        return $this->transform(fn($val) => is_string($val) ? (preg_replace('/[^\w]/u', '', $val) ?? $val) : $val);
    }

    public function coerceDate(string $format = 'Y-m-d'): static
    {
        return $this->transform(function ($val) use ($format) {
            if ($val instanceof \DateTimeImmutable) {
                return $val;
            }
            if (is_string($val) && trim($val) !== '') {
                $d = \DateTimeImmutable::createFromFormat('!' . $format, trim($val));
                if ($d !== false) {
                    return $d;
                }
                return new \DateTimeImmutable(trim($val));
            }
            return $val;
        });
    }
}
