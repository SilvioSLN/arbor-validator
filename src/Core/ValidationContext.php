<?php

declare(strict_types=1);

namespace Arbor\Validator\Core;

use Arbor\Validator\I18n\Translator;

class ValidationContext
{
    private static bool $testingMode = false;

    public function __construct(
        public readonly string $path = '',
        public readonly mixed $rootData = null,
        public readonly ErrorBag $errorBag = new ErrorBag(),
        public readonly ?string $locale = null,
    ) {
    }

    public static function setTestingMode(bool $enabled): void
    {
        self::$testingMode = $enabled;
    }

    public static function isTestingMode(): bool
    {
        return self::$testingMode;
    }

    public function createChild(string $subPath): self
    {
        $newPath = $this->path === '' ? $subPath : "{$this->path}.{$subPath}";
        return new self($newPath, $this->rootData, $this->errorBag, $this->locale);
    }

    public function addError(string $message, ?string $customPath = null): void
    {
        $targetPath = $customPath ?? $this->path;
        $this->errorBag->add($targetPath, $message);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function addErrorByKey(string $key, array $params = [], ?string $customPath = null): void
    {
        $targetPath = $customPath ?? $this->path;
        if (!isset($params['attribute'])) {
            $params['attribute'] = $targetPath !== '' ? $targetPath : ':attribute';
        }

        $message = Translator::getInstance()->get($key, $params, $this->locale);
        $this->errorBag->add($targetPath, $message);
    }

    public function getRootValue(string $key): mixed
    {
        if (!is_array($this->rootData)) {
            return null;
        }

        // Suporta chaves diretas e chaves pontilhadas 'user.name'
        if (array_key_exists($key, $this->rootData)) {
            return $this->rootData[$key];
        }

        $segments = explode('.', $key);
        $curr = $this->rootData;
        foreach ($segments as $segment) {
            if (is_array($curr) && array_key_exists($segment, $curr)) {
                $curr = $curr[$segment];
            } else {
                return null;
            }
        }

        return $curr;
    }
}
