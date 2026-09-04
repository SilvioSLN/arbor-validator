<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Files\UploadedFile;
use Arbor\Validator\Rules\UploadedFileRule;

class FileSchema extends Schema
{
    protected string|int|null $maxSize = null;
    protected string|int|null $minSize = null;

    /**
     * @var list<string>
     */
    protected array $extensions = [];

    /**
     * @var list<string>
     */
    protected array $mimeTypes = [];

    protected bool $allowNonUploadedFiles = false;

    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        $rule = new UploadedFileRule(
            maxSize: $this->maxSize,
            minSize: $this->minSize,
            extensions: $this->extensions,
            mimeTypes: $this->mimeTypes,
            allowNonUploadedFiles: $this->allowNonUploadedFiles,
        );

        if (!$rule->validate($value, $context)) {
            return $value;
        }

        return $rule->resolveFile($value);
    }

    public function maxSize(string|int $size): static
    {
        $clone = clone $this;
        $clone->maxSize = $size;
        return $clone;
    }

    public function minSize(string|int $size): static
    {
        $clone = clone $this;
        $clone->minSize = $size;
        return $clone;
    }

    /**
     * @param list<string>|string $extensions
     */
    public function extension(array|string $extensions): static
    {
        $clone = clone $this;
        $clone->extensions = is_array($extensions) ? $extensions : [$extensions];
        return $clone;
    }

    /**
     * @param list<string>|string $mimeTypes
     */
    public function mimeType(array|string $mimeTypes): static
    {
        $clone = clone $this;
        $clone->mimeTypes = is_array($mimeTypes) ? $mimeTypes : [$mimeTypes];
        return $clone;
    }

    public function allowNonUploadedFiles(bool $allow = true): static
    {
        $clone = clone $this;
        $clone->allowNonUploadedFiles = $allow;
        return $clone;
    }
}
