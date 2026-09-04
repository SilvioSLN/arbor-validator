<?php

declare(strict_types=1);

namespace Arbor\Validator\Rules;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Files\UploadedFile;

class UploadedFileRule implements RuleInterface
{
    /**
     * @param list<string> $extensions
     * @param list<string> $mimeTypes
     */
    public function __construct(
        public readonly string|int|null $maxSize = null,
        public readonly string|int|null $minSize = null,
        public readonly array $extensions = [],
        public readonly array $mimeTypes = [],
        public readonly bool $allowNonUploadedFiles = false,
        public readonly ?string $message = null,
    ) {
    }

    public function validate(mixed $value, ValidationContext $context): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $uploadedFile = $this->resolveFile($value);
        if ($uploadedFile === null) {
            $context->addErrorByKey('file_invalid');
            return false;
        }

        if ($this->allowNonUploadedFiles) {
            ValidationContext::setTestingMode(true);
        }

        if (!$uploadedFile->isValid()) {
            $this->fail($context, 'file_invalid');
            return false;
        }

        // Validação de tamanho máximo
        if ($this->maxSize !== null) {
            $maxBytes = UploadedFile::parseSizeToBytes($this->maxSize);
            if ($uploadedFile->getSize() > $maxBytes) {
                $this->fail($context, 'file_max_size', ['max' => (string) $this->maxSize]);
                return false;
            }
        }

        // Validação de tamanho mínimo
        if ($this->minSize !== null) {
            $minBytes = UploadedFile::parseSizeToBytes($this->minSize);
            if ($uploadedFile->getSize() < $minBytes) {
                $this->fail($context, 'file_min_size', ['min' => (string) $this->minSize]);
                return false;
            }
        }

        // Validação da extensão do nome original
        if (!empty($this->extensions)) {
            $ext = $uploadedFile->getExtension();
            $allowedExts = array_map('strtolower', $this->extensions);
            if (!in_array($ext, $allowedExts, true)) {
                $this->fail($context, 'file_extension', ['extensions' => $allowedExts]);
                return false;
            }
        }

        // Validação REAL do MIME type via finfo magic bytes no tmp_name
        if (!empty($this->mimeTypes)) {
            $realMime = $uploadedFile->getRealMimeType();
            $allowedMimes = array_map('strtolower', $this->mimeTypes);
            if (!in_array(strtolower($realMime), $allowedMimes, true)) {
                $this->fail($context, 'file_mime_type', [
                    'mime' => $realMime,
                    'types' => $allowedMimes,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function fail(ValidationContext $context, string $defaultKey, array $params = []): void
    {
        if ($this->message !== null) {
            $context->addError($this->message);
        } else {
            $context->addErrorByKey($defaultKey, $params);
        }
    }

    public function resolveFile(mixed $value): ?UploadedFile
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
