<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Rules\DomainRule;
use Arbor\Validator\Rules\EmojisRule;
use Arbor\Validator\Rules\NoHtmlRule;
use Arbor\Validator\Rules\UrlRule;
use Arbor\Validator\Rules\UuidRule;
use PHPUnit\Framework\TestCase;

final class WebAndSecurityRuleTest extends TestCase
{
    public function testDomainValidation(): void
    {
        $this->assertTrue(DomainRule::isValid('google.com'));
        $this->assertTrue(DomainRule::isValid('meusite.com.br'));
        $this->assertTrue(DomainRule::isValid('sub.domain.org'));

        // Inválidos
        $this->assertFalse(DomainRule::isValid('http://google.com')); // Não pode ter protocolo
        $this->assertFalse(DomainRule::isValid('google.com/path'));   // Não pode ter caminho
        $this->assertFalse(DomainRule::isValid('-invalid.com'));
    }

    public function testUrlValidation(): void
    {
        $this->assertTrue(UrlRule::isValid('https://arborphp.org/docs'));
        $this->assertTrue(UrlRule::isValid('http://localhost:8000'));

        $this->assertFalse(UrlRule::isValid('ftp://ftp.example.com')); // ftp não permitido por padrão
        $this->assertFalse(UrlRule::isValid('not-a-url'));
    }

    public function testUuidValidation(): void
    {
        $v4 = '550e8400-e29b-41d4-a716-446655440000';
        $this->assertTrue(UuidRule::isValid($v4, version: 4));
        $this->assertTrue(UuidRule::isValid($v4, version: 0));

        $this->assertFalse(UuidRule::isValid('invalid-uuid-string'));
    }

    public function testNoHtmlRule(): void
    {
        $this->assertTrue(NoHtmlRule::isValid('Texto simples sem nenhuma tag.'));
        $this->assertTrue(NoHtmlRule::isValid('2 < 3 e 5 > 4'));

        $this->assertFalse(NoHtmlRule::isValid('Texto com <script>alert("xss")</script>'));
        $this->assertFalse(NoHtmlRule::isValid('Texto com <b>negrito</b>'));
    }

    public function testEmojisRule(): void
    {
        $withEmoji = 'Olá Mundo! 🎉🚀';
        $onlyEmoji = '🎉🚀🔥';
        $noEmoji = 'Olá Mundo!';

        // Allow false
        $context = new ValidationContext('text');
        $ruleBlock = new EmojisRule(allow: false);
        $this->assertFalse($ruleBlock->validate($withEmoji, $context));
        $this->assertTrue($ruleBlock->validate($noEmoji, new ValidationContext('text')));

        // Only true
        $contextOnly = new ValidationContext('text');
        $ruleOnly = new EmojisRule(allow: true, only: true);
        $this->assertTrue($ruleOnly->validate($onlyEmoji, $contextOnly));
        $this->assertFalse($ruleOnly->validate($withEmoji, new ValidationContext('text')));
    }
}
