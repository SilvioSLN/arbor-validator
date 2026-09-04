<?php

declare(strict_types=1);

namespace Arbor\Validator\Files;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Exceptions\ValidatorException;

class UploadedFile
{
    private ?string $realMimeType = null;

    public function __construct(
        public readonly string $tmpName,
        public readonly string $clientName,
        public readonly int $size,
        public readonly int $error = UPLOAD_ERR_OK,
        public readonly ?string $clientMimeType = null,
    ) {
    }

    /**
     * Cria uma instância a partir do array nativo de $_FILES.
     *
     * @param array<string, mixed> $file
     */
    public static function fromArray(array $file): self
    {
        return new self(
            tmpName: (string) ($file['tmp_name'] ?? ''),
            clientName: (string) ($file['name'] ?? ''),
            size: (int) ($file['size'] ?? 0),
            error: (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
            clientMimeType: isset($file['type']) ? (string) $file['type'] : null,
        );
    }

    public function isValid(): bool
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            return false;
        }

        if (empty($this->tmpName) || !file_exists($this->tmpName)) {
            return false;
        }

        if (ValidationContext::isTestingMode()) {
            return true;
        }

        return is_uploaded_file($this->tmpName);
    }

    public function getClientFilename(): string
    {
        return $this->clientName;
    }

    public function clientName(): string
    {
        return $this->clientName;
    }

    public function getClientMimeType(): ?string
    {
        return $this->clientMimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function getRealPath(): string
    {
        return $this->tmpName;
    }

    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Retorna a extensão do arquivo baseada no nome original enviado pelo cliente (em minúsculas).
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->clientName, PATHINFO_EXTENSION));
    }

    public function extension(): string
    {
        return $this->getExtension();
    }

    /**
     * Retorna o MIME type REAL do arquivo inspecionando seus magic bytes com finfo.
     */
    public function getRealMimeType(): string
    {
        if ($this->realMimeType !== null) {
            return $this->realMimeType;
        }

        if (!file_exists($this->tmpName)) {
            return 'application/octet-stream';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return 'application/octet-stream';
        }

        $mime = finfo_file($finfo, $this->tmpName);
        if (PHP_VERSION_ID < 80500) {
            finfo_close($finfo);
        }

        $this->realMimeType = $mime !== false ? $mime : 'application/octet-stream';
        return $this->realMimeType;
    }

    public function mimeType(): string
    {
        return $this->getRealMimeType();
    }

    /**
     * Move o arquivo para o destino informado com segurança.
     *
     * @throws ValidatorException
     */
    public function moveTo(string $targetPath): bool
    {
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new ValidatorException("Não foi possível criar o diretório de destino: {$targetDir}");
            }
        }

        if (ValidationContext::isTestingMode() || !is_uploaded_file($this->tmpName)) {
            return rename($this->tmpName, $targetPath);
        }

        return move_uploaded_file($this->tmpName, $targetPath);
    }

    /**
     * Converte strings amigáveis como '5MB', '500KB', '2GB' em número de bytes.
     */
    public static function parseSizeToBytes(string|int $size): int
    {
        if (is_int($size)) {
            return $size;
        }

        $size = trim($size);
        if (is_numeric($size)) {
            return (int) $size;
        }

        $unit = strtoupper(substr($size, -2));
        $value = (float) substr($size, 0, -2);

        return match ($unit) {
            'KB' => (int) round($value * 1024),
            'MB' => (int) round($value * 1024 * 1024),
            'GB' => (int) round($value * 1024 * 1024 * 1024),
            'TB' => (int) round($value * 1024 * 1024 * 1024 * 1024),
            default => match (strtoupper(substr($size, -1))) {
                'K' => (int) round(((float) substr($size, 0, -1)) * 1024),
                'M' => (int) round(((float) substr($size, 0, -1)) * 1024 * 1024),
                'G' => (int) round(((float) substr($size, 0, -1)) * 1024 * 1024 * 1024),
                'B' => (int) round((float) substr($size, 0, -1)),
                default => (int) $size,
            },
        };
    }

    /**
     * Converte o objeto de volta em array nativo do $_FILES se necessário.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->clientName,
            'tmp_name' => $this->tmpName,
            'size' => $this->size,
            'error' => $this->error,
            'type' => $this->clientMimeType ?? $this->getRealMimeType(),
        ];
    }
}
