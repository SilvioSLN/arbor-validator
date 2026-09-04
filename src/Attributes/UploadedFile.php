<?php

declare(strict_types=1);

namespace Arbor\Validator\Attributes;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\UploadedFileRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class UploadedFile implements ValidationAttributeInterface
{
    /**
     * @param list<string> $extensions
     * @param list<string> $mimeTypes
     */
    public function __construct(
        public string|int|null $maxSize = null,
        public string|int|null $minSize = null,
        public array $extensions = [],
        public array $mimeTypes = [],
        public bool $allowNonUploadedFiles = false,
        public ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        $rule = new UploadedFileRule(
            maxSize: $this->maxSize,
            minSize: $this->minSize,
            extensions: $this->extensions,
            mimeTypes: $this->mimeTypes,
            allowNonUploadedFiles: $this->allowNonUploadedFiles,
            message: $this->message,
        );

        return $rule->validate($value, $context);
    }
}
