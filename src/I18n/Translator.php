<?php

declare(strict_types=1);

namespace Arbor\Validator\I18n;

class Translator
{
    private static ?self $instance = null;

    private string $locale = 'pt-BR';

    /**
     * @var array<string, array<string, string>>
     */
    private array $catalogs = [];

    public function __construct()
    {
        $this->loadDefaultCatalogs();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param array<string, string> $messages
     */
    public function addMessages(string $locale, array $messages): void
    {
        $this->catalogs[$locale] = array_merge($this->catalogs[$locale] ?? [], $messages);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function get(string $key, array $params = [], ?string $locale = null): string
    {
        $targetLocale = $locale ?? $this->locale;

        $template = $this->catalogs[$targetLocale][$key]
            ?? $this->catalogs['pt-BR'][$key]
            ?? $key;

        foreach ($params as $paramKey => $val) {
            if (is_array($val)) {
                $val = implode(', ', array_map('strval', $val));
            } elseif ($val instanceof \DateTimeInterface) {
                $val = $val->format('Y-m-d H:i:s');
            } elseif (is_bool($val)) {
                $val = $val ? 'true' : 'false';
            } else {
                $val = (string) $val;
            }

            $template = str_replace(':' . $paramKey, $val, $template);
        }

        return $template;
    }

    private function loadDefaultCatalogs(): void
    {
        $ptBR = require __DIR__ . '/Messages_pt_BR.php';
        $en = require __DIR__ . '/Messages_en.php';

        $this->catalogs['pt-BR'] = $ptBR;
        $this->catalogs['pt_BR'] = $ptBR;
        $this->catalogs['pt'] = $ptBR;

        $this->catalogs['en'] = $en;
        $this->catalogs['en-US'] = $en;
        $this->catalogs['en_US'] = $en;
    }
}
