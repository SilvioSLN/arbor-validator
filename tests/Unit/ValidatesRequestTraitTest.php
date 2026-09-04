<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\AV;
use Arbor\Validator\Integration\ValidatesRequestTrait;
use PHPUnit\Framework\TestCase;

class RequestWithAll
{
    use ValidatesRequestTrait;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return ['param' => 'all_val'];
    }

    /**
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return ['doc' => ['tmp_name' => 'f.txt']];
    }
}

class RequestWithParsedBody
{
    use ValidatesRequestTrait;

    /**
     * @return array<string, mixed>
     */
    public function getParsedBody(): array
    {
        return ['param' => 'body_val'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUploadedFiles(): array
    {
        return ['doc' => ['tmp_name' => 'b.txt']];
    }
}

class RequestWithGlobals
{
    use ValidatesRequestTrait;
}

final class ValidatesRequestTraitTest extends TestCase
{
    public function testRequestWithAllAndFiles(): void
    {
        $req = new RequestWithAll();
        $res = $req->validate(['param' => AV::string()]);
        $this->assertTrue($res->isValid());
        $this->assertSame('all_val', $res->data()['param']);
    }

    public function testRequestWithParsedBodyAndUploadedFiles(): void
    {
        $req = new RequestWithParsedBody();
        $res = $req->validate(['param' => AV::string()]);
        $this->assertTrue($res->isValid());
        $this->assertSame('body_val', $res->data()['param']);
    }

    public function testRequestWithGlobalsFallback(): void
    {
        $_GET['from_get'] = 'get_val';
        $_POST['from_post'] = 'post_val';
        $_FILES['from_files'] = ['tmp_name' => 'g.txt'];

        $req = new RequestWithGlobals();
        $res = $req->validate([
            'from_get' => AV::string(),
            'from_post' => AV::string(),
        ]);

        $this->assertTrue($res->isValid());
        $this->assertSame('get_val', $res->data()['from_get']);
        $this->assertSame('post_val', $res->data()['from_post']);

        unset($_GET['from_get'], $_POST['from_post'], $_FILES['from_files']);
    }
}
