<?php

declare(strict_types=1);

namespace Arbor\Validator;

use Arbor\Validator\Core\ClassMapper;
use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Core\ValidationResult;
use Arbor\Validator\Exceptions\ValidationException;
use Arbor\Validator\I18n\Translator;
use Arbor\Validator\Schemas\Schema;

/**
 * Main facade for Arbor Validator.
 *
 * Provides entry points for validating data against DTO classes (Class-First approach),
 * fluent Schemas (Zod-like approach), or associative schema arrays.
 *
 * @api
 */
final class ArborValidator
{
    /**
     * Validates input data against a target DTO class, Schema instance, or associative shape array.
     * Returns a ValidationResult object containing success status, clean data, and error bag.
     *
     * @template T of object
     * @param class-string<T>|Schema|array<string, Schema> $target DTO class name, Schema instance, or associative array of schemas.
     * @param mixed $data Raw input data (typically associative array, $_POST, or request payload).
     * @param string|null $locale Optional locale override (e.g. 'pt-BR', 'en'). Defaults to current global locale.
     * @return ValidationResult Validation result object. Call ->isValid(), ->failed(), ->data(), ->errors().
     */
    public static function validate(string|Schema|array $target, mixed $data, ?string $locale = null): ValidationResult
    {
        // Se for uma classe DTO
        if (is_string($target)) {
            $context = new ValidationContext(path: '', rootData: $data, locale: $locale);
            $mapper = new ClassMapper();
            $dto = is_array($data) ? $mapper->validateAndMap($target, $data, $context) : null;

            if (!is_array($data)) {
                $context->addError('Os dados para o DTO devem ser um array associativo.');
            }

            if ($context->errorBag->isNotEmpty()) {
                return ValidationResult::failure($context->errorBag, $dto);
            }

            return ValidationResult::success($dto);
        }

        // Se for um Schema Zod-like
        if ($target instanceof Schema) {
            return $target->safeParse($data, $locale);
        }

        // Se for um array associativo de Schemas: ['nome' => AV::string(), ...]
        return AV::shape($target)->safeParse(is_array($data) ? $data : [], $locale);
    }

    /**
     * Validates input data and returns the cleaned data or instantiated DTO.
     * Throws ValidationException if validation fails.
     *
     * @template T of object
     * @param class-string<T>|Schema|array<string, Schema> $target DTO class name, Schema instance, or associative array of schemas.
     * @param mixed $data Raw input data.
     * @param string|null $locale Optional locale override (e.g. 'pt-BR', 'en').
     * @return ($target is class-string<T> ? T : mixed) Instantiated DTO or sanitized array data.
     * @throws ValidationException If validation fails.
     */
    public static function parse(string|Schema|array $target, mixed $data, ?string $locale = null): mixed
    {
        $result = self::validate($target, $data, $locale);
        return $result->data();
    }

    /**
     * Sets the default global locale for validation error messages (e.g. 'pt-BR', 'en').
     *
     * @param string $locale Locale identifier (e.g. 'pt-BR', 'en').
     */
    public static function setLocale(string $locale): void
    {
        Translator::getInstance()->setLocale($locale);
    }

    /**
     * Registers or overrides error message translations for a specific locale.
     *
     * @param string $locale Locale identifier (e.g. 'pt-BR', 'en').
     * @param array<string, string> $messages Associative array of message templates key => message.
     */
    public static function addMessages(string $locale, array $messages): void
    {
        Translator::getInstance()->addMessages($locale, $messages);
    }

    /**
     * Toggles testing mode for file uploads.
     * When enabled, allows validating and moving non-HTTP-uploaded files (useful for PHPUnit, CLI, and seeders).
     *
     * @param bool $enabled True to enable testing mode, false to disable.
     */
    public static function setTestingMode(bool $enabled = true): void
    {
        ValidationContext::setTestingMode($enabled);
    }
}
