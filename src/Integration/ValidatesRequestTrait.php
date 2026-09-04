<?php

declare(strict_types=1);

namespace Arbor\Validator\Integration;

use Arbor\Validator\ArborValidator;
use Arbor\Validator\Core\ValidationResult;
use Arbor\Validator\Schemas\Schema;

trait ValidatesRequestTrait
{
    /**
     * Valida os dados da requisição contra um DTO ou Schema fluente.
     *
     * @param class-string|Schema|array<string, Schema> $target
     * @param array<string, mixed>|null $customData
     */
    public function validate(string|Schema|array $target, ?array $customData = null): ValidationResult
    {
        $payload = $customData ?? $this->extractRequestPayload();
        return ArborValidator::validate($target, $payload);
    }

    /**
     * Valida e retorna os dados limpos ou DTO, lançando ValidationException em caso de erro.
     *
     * @param class-string|Schema|array<string, Schema> $target
     * @param array<string, mixed>|null $customData
     */
    public function validateOrFail(string|Schema|array $target, ?array $customData = null): mixed
    {
        $payload = $customData ?? $this->extractRequestPayload();
        return ArborValidator::parse($target, $payload);
    }

    /**
     * Extrai os dados e arquivos da requisição suportando Arbor Router e frameworks PSR.
     *
     * @return array<string, mixed>
     */
    protected function extractRequestPayload(): array
    {
        $data = [];

        // Arbor Router / Laravel / Slim Request métodos comuns
        if (is_callable([$this, 'all'])) {
            $data = (array) $this->all();
        } elseif (is_callable([$this, 'inputs'])) {
            $data = (array) $this->inputs();
        } elseif (is_callable([$this, 'getParsedBody'])) {
            $body = $this->getParsedBody();
            $data = is_array($body) ? $body : [];
        } else {
            // Fallback para variáveis globais PHP
            $data = array_merge($_GET, $_POST);
        }

        // Incorpora arquivos enviados
        $files = [];
        if (is_callable([$this, 'files'])) {
            $files = (array) $this->files();
        } elseif (is_callable([$this, 'getUploadedFiles'])) {
            $files = (array) $this->getUploadedFiles();
        } elseif (!empty($_FILES)) {
            $files = $_FILES;
        }

        return array_merge($data, $files);
    }
}
