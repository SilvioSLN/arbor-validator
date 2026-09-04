<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Files\UploadedFile;
use PHPUnit\Framework\TestCase;

final class UploadedFileCompleteTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/arbor_upfile_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if ($files !== false) {
            foreach ($files as $f) {
                if (is_file($f)) {
                    unlink($f);
                }
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testUploadedFilePropertiesAndHelpers(): void
    {
        $tmp = $this->tempDir . '/test.txt';
        file_put_contents($tmp, 'Hello world');

        $file = UploadedFile::fromArray([
            'tmp_name' => $tmp,
            'name' => 'document.txt',
            'size' => 11,
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/plain',
        ]);

        $this->assertSame('document.txt', $file->getClientFilename());
        $this->assertSame('document.txt', $file->clientName());
        $this->assertSame('text/plain', $file->getClientMimeType());
        $this->assertSame(11, $file->getSize());
        $this->assertSame($tmp, $file->getRealPath());
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
        $this->assertSame('txt', $file->getExtension());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('text/plain', $file->getRealMimeType());
        $this->assertSame('text/plain', $file->mimeType());

        $array = $file->toArray();
        $this->assertSame('document.txt', $array['name']);
        $this->assertSame($tmp, $array['tmp_name']);
        $this->assertSame(11, $array['size']);

        // Teste de moveTo
        ValidationContext::setTestingMode(true);
        $target = $this->tempDir . '/nested/destination/dest.txt';
        $this->assertTrue($file->moveTo($target));
        $this->assertFileExists($target);
        $this->assertSame('Hello world', file_get_contents($target));
        unlink($target);
        rmdir(dirname($target));
        rmdir(dirname(dirname($target)));
    }

    public function testInvalidStates(): void
    {
        // Arquivo que não existe
        $missing = new UploadedFile('/caminho/nao/existe', 'arquivo.txt', 100);
        $this->assertSame('application/octet-stream', $missing->getRealMimeType());
        $this->assertFalse($missing->isValid());

        // Arquivo com erro de upload
        $errFile = new UploadedFile($this->tempDir, 'arquivo.txt', 100, error: UPLOAD_ERR_INI_SIZE);
        $this->assertFalse($errFile->isValid());
    }

    public function testParseSizeToBytesUnits(): void
    {
        $this->assertSame(10 * 1024 * 1024 * 1024 * 1024, UploadedFile::parseSizeToBytes('10TB'));
        $this->assertSame(512 * 1024, UploadedFile::parseSizeToBytes('512KB'));
        $this->assertSame(2 * 1024 * 1024, UploadedFile::parseSizeToBytes('2MB'));
        $this->assertSame(1 * 1024 * 1024 * 1024, UploadedFile::parseSizeToBytes('1GB'));

        // Sufixos curtos de uma letra (K, M, G, B)
        $this->assertSame(4 * 1024, UploadedFile::parseSizeToBytes('4K'));
        $this->assertSame(8 * 1024 * 1024, UploadedFile::parseSizeToBytes('8M'));
        $this->assertSame(3 * 1024 * 1024 * 1024, UploadedFile::parseSizeToBytes('3G'));
        $this->assertSame(500, UploadedFile::parseSizeToBytes('500B'));
        $this->assertSame(100, UploadedFile::parseSizeToBytes('100'));
    }
}
