<?php

declare(strict_types=1);

namespace Arbor\Validator\Core;

use Arbor\Validator\Attributes\Coerce;
use Arbor\Validator\Attributes\Date;
use Arbor\Validator\Attributes\DateTime;
use Arbor\Validator\Attributes\Each;
use Arbor\Validator\Attributes\Nested;
use Arbor\Validator\Attributes\Nullable;
use Arbor\Validator\Attributes\Optional;
use Arbor\Validator\Attributes\Required;
use Arbor\Validator\Attributes\Time;
use Arbor\Validator\Attributes\Transform;
use Arbor\Validator\Attributes\UploadedFile as UploadedFileAttr;
use Arbor\Validator\Attributes\ValidationAttributeInterface;
use Arbor\Validator\Files\UploadedFile;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

class ClassMapper
{
    /**
     * @param class-string $dtoClass
     * @param array<string, mixed> $data
     */
    public function validateAndMap(string $dtoClass, array $data, ValidationContext $context): ?object
    {
        if (!class_exists($dtoClass)) {
            $context->addError("Classe DTO não encontrada: {$dtoClass}");
            return null;
        }

        $reflection = new ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $args = [];
        $parameters = $constructor->getParameters();

        foreach ($parameters as $param) {
            $paramName = $param->getName();
            $childContext = $context->createChild($paramName);

            $value = $this->extractValue($paramName, $data);
            $attributes = $this->collectAttributes($param, $reflection);

            $type = $param->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
            $allowsNull = $type?->allowsNull() ?? true;

            $hasRequired = $this->hasAttribute($attributes, Required::class);
            $hasOptional = $this->hasAttribute($attributes, Optional::class) || $param->isDefaultValueAvailable();
            $isNullable = $allowsNull || $this->hasAttribute($attributes, Nullable::class);

            // Se o campo não foi enviado
            if ($value === null && !array_key_exists($paramName, $data)) {
                if ($hasOptional) {
                    $args[$paramName] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                    continue;
                }

                if ($hasRequired || !$isNullable) {
                    $childContext->addErrorByKey('required');
                    $args[$paramName] = null;
                    continue;
                }

                $args[$paramName] = null;
                continue;
            }

            // Se o campo veio vazio ("" ou null)
            if ($value === null || $value === '') {
                if ($hasRequired) {
                    $childContext->addErrorByKey('required');
                    $args[$paramName] = null;
                    continue;
                }

                if ($isNullable || $hasOptional) {
                    $args[$paramName] = ($value === null || !$isNullable) && $param->isDefaultValueAvailable()
                        ? $param->getDefaultValue()
                        : null;
                    continue;
                }
            }

            // Trata upload de arquivo
            $uploadedFileAttr = $this->getAttribute($attributes, UploadedFileAttr::class);
            if ($uploadedFileAttr !== null || $typeName === UploadedFile::class) {
                $file = $this->resolveUploadedFile($value);
                if ($file !== null) {
                    $rule = new \Arbor\Validator\Rules\UploadedFileRule(
                        maxSize: $uploadedFileAttr?->maxSize,
                        minSize: $uploadedFileAttr?->minSize,
                        extensions: $uploadedFileAttr !== null ? $uploadedFileAttr->extensions : [],
                        mimeTypes: $uploadedFileAttr !== null ? $uploadedFileAttr->mimeTypes : [],
                        allowNonUploadedFiles: $uploadedFileAttr !== null ? $uploadedFileAttr->allowNonUploadedFiles : false,
                        message: $uploadedFileAttr?->message,
                    );

                    $rule->validate($file, $childContext);

                    if ($typeName === 'array') {
                        $value = $file->toArray();
                    } else {
                        $value = $file;
                    }
                } elseif ($hasRequired) {
                    $childContext->addErrorByKey('file_required');
                }
            }

            // Suporte a DTOs aninhados
            $nestedAttr = $this->getAttribute($attributes, Nested::class);
            $nestedClass = $nestedAttr?->dtoClass;
            if ($nestedClass === null && $typeName !== null && class_exists($typeName) && !str_starts_with($typeName, 'DateTime') && $typeName !== UploadedFile::class) {
                $nestedClass = $typeName;
            }

            if ($nestedClass !== null && is_array($value)) {
                $value = $this->validateAndMap($nestedClass, $value, $childContext);
            }

            // Suporte a listas de itens (#[V\Each])
            $eachAttr = $this->getAttribute($attributes, Each::class);
            if ($eachAttr !== null && is_array($value)) {
                $cleanList = [];
                foreach ($value as $idx => $item) {
                    $itemContext = $childContext->createChild((string) $idx);
                    if (class_exists($eachAttr->type) && is_array($item)) {
                        $cleanList[$idx] = $this->validateAndMap($eachAttr->type, $item, $itemContext);
                    } else {
                        $cleanList[$idx] = Coercer::coerce($item, $eachAttr->type, false);
                    }
                }
                $value = $cleanList;
            }

            // Coerção de tipos inteligente
            $dateFormat = $this->resolveDateFormat($attributes);
            $value = Coercer::coerce($value, $typeName, $isNullable, $dateFormat);

            // Validações por atributos
            foreach ($attributes as $attrInstance) {
                if ($attrInstance instanceof ValidationAttributeInterface) {
                    if (!$attrInstance->validate($value, $childContext)) {
                        continue;
                    }

                    if (method_exists($attrInstance, 'sanitize')) {
                        $value = $attrInstance->sanitize($value);
                    }
                }

                if ($attrInstance instanceof Transform) {
                    $value = $attrInstance->transform($value);
                }
            }

            $args[$paramName] = $value;
        }

        if ($context->errorBag->isNotEmpty()) {
            return null;
        }

        try {
            return $reflection->newInstanceArgs($args);
        } catch (\Throwable $e) {
            $context->addError("Erro ao instanciar DTO {$dtoClass}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractValue(string $paramName, array $data): mixed
    {
        if (array_key_exists($paramName, $data)) {
            return $data[$paramName];
        }

        // Tenta converter camelCase para snake_case se não encontrar
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $paramName) ?? '');
        if (array_key_exists($snake, $data)) {
            return $data[$snake];
        }

        return null;
    }

    /**
     * @param ReflectionClass<object> $reflection
     * @return list<object>
     */
    private function collectAttributes(ReflectionParameter $param, ReflectionClass $reflection): array
    {
        $instances = [];

        foreach ($param->getAttributes() as $attr) {
            $instances[] = $attr->newInstance();
        }

        // Também busca se a propriedade existir na classe com atributos (apenas se não for promovida)
        if (!$param->isPromoted() && $reflection->hasProperty($param->getName())) {
            $prop = $reflection->getProperty($param->getName());
            foreach ($prop->getAttributes() as $attr) {
                $instances[] = $attr->newInstance();
            }
        }

        return $instances;
    }

    /**
     * @param list<object> $attributes
     * @param class-string $className
     */
    private function hasAttribute(array $attributes, string $className): bool
    {
        foreach ($attributes as $attr) {
            if ($attr instanceof $className) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template T of object
     * @param list<object> $attributes
     * @param class-string<T> $className
     * @return T|null
     */
    private function getAttribute(array $attributes, string $className): ?object
    {
        foreach ($attributes as $attr) {
            if ($attr instanceof $className) {
                return $attr;
            }
        }

        return null;
    }

    /**
     * @param list<object> $attributes
     */
    private function resolveDateFormat(array $attributes): ?string
    {
        $date = $this->getAttribute($attributes, Date::class);
        if ($date !== null) {
            return $date->format;
        }

        $dateTime = $this->getAttribute($attributes, DateTime::class);
        if ($dateTime !== null) {
            return $dateTime->format;
        }

        $time = $this->getAttribute($attributes, Time::class);
        if ($time !== null) {
            return $time->format;
        }

        $coerce = $this->getAttribute($attributes, Coerce::class);
        if ($coerce !== null) {
            return $coerce->dateFormat;
        }

        return null;
    }

    private function resolveUploadedFile(mixed $value): ?UploadedFile
    {
        if ($value instanceof UploadedFile) {
            return $value;
        }

        if (is_array($value) && isset($value['tmp_name'])) {
            return UploadedFile::fromArray($value);
        }

        return null;
    }
}
