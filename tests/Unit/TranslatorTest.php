<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\I18n\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Translator::reset();
    }

    public function testDefaultPtBrTranslation(): void
    {
        $t = Translator::getInstance();
        $this->assertSame('pt-BR', $t->getLocale());

        $msg = $t->get('required', ['attribute' => 'nome']);
        $this->assertSame('O campo nome é obrigatório.', $msg);
    }

    public function testSwitchToEnglish(): void
    {
        $t = Translator::getInstance();
        $t->setLocale('en');

        $msg = $t->get('required', ['attribute' => 'email']);
        $this->assertSame('The email field is required.', $msg);
    }

    public function testCustomMessages(): void
    {
        $t = Translator::getInstance();
        $t->addMessages('pt-BR', [
            'custom_rule' => 'Erro personalizado para :attribute.',
        ]);

        $msg = $t->get('custom_rule', ['attribute' => 'codigo']);
        $this->assertSame('Erro personalizado para codigo.', $msg);
    }
}
