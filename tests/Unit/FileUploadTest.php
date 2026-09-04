<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Unit;

use Arbor\Validator\Core\ValidationContext;
use Arbor\Validator\Files\UploadedFile;
use Arbor\Validator\Rules\UploadedFileRule;
use PHPUnit\Framework\TestCase;

final class FileUploadTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/arbor_validator_tests_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        ValidationContext::setTestingMode(true);
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

    public function testRealImageValidationPasses(): void
    {
        // 1x1 GIF válido (magic bytes: GIF89a...)
        $gifData = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $tmpPath = $this->tempDir . '/valid.gif';
        file_put_contents($tmpPath, $gifData);

        $file = new UploadedFile(
            tmpName: $tmpPath,
            clientName: 'avatar.gif',
            size: strlen($gifData),
            error: UPLOAD_ERR_OK,
            clientMimeType: 'image/gif'
        );

        $this->assertSame('image/gif', $file->getRealMimeType());

        $rule = new UploadedFileRule(
            maxSize: '1MB',
            extensions: ['gif', 'png'],
            mimeTypes: ['image/gif', 'image/png'],
            allowNonUploadedFiles: true,
        );

        $context = new ValidationContext('avatar');
        $this->assertTrue($rule->validate($file, $context));
        $this->assertTrue($context->errorBag->isEmpty());
    }

    public function testSpoofedMimeTypeIsRejectedViaMagicBytes(): void
    {
        // Script malicioso ou texto fingindo ser imagem JPG
        $phpCode = '<?php echo "I am not an image"; ?>';
        $tmpPath = $this->tempDir . '/malicious.tmp';
        file_put_contents($tmpPath, $phpCode);

        // O cliente envia com extensão .jpg e declara no cabeçalho type = image/jpeg
        $spoofedFile = new UploadedFile(
            tmpName: $tmpPath,
            clientName: 'hacker.jpg',
            size: strlen($phpCode),
            error: UPLOAD_ERR_OK,
            clientMimeType: 'image/jpeg' // Forjado pelo cliente!
        );

        // O finfo_file deve detectar que NÃO é image/jpeg!
        $this->assertNotSame('image/jpeg', $spoofedFile->getRealMimeType());

        $rule = new UploadedFileRule(
            extensions: ['jpg', 'jpeg'],
            mimeTypes: ['image/jpeg'],
            allowNonUploadedFiles: true,
        );

        $context = new ValidationContext('avatar');
        $this->assertFalse($rule->validate($spoofedFile, $context));
        $this->assertTrue($context->errorBag->has('avatar'));
    }

    public function testFileSizeLimit(): void
    {
        $largeData = str_repeat('A', 2048); // 2KB
        $tmpPath = $this->tempDir . '/large.tmp';
        file_put_contents($tmpPath, $largeData);

        $file = new UploadedFile(
            tmpName: $tmpPath,
            clientName: 'file.txt',
            size: 2048,
            error: UPLOAD_ERR_OK,
        );

        $rule = new UploadedFileRule(
            maxSize: '1KB', // Limite de 1024 bytes
            allowNonUploadedFiles: true,
        );

        $context = new ValidationContext('doc');
        $this->assertFalse($rule->validate($file, $context));
        $this->assertTrue($context->errorBag->has('doc'));
    }

    public function testParseSizeToBytes(): void
    {
        $this->assertSame(1024, UploadedFile::parseSizeToBytes('1KB'));
        $this->assertSame(5 * 1024 * 1024, UploadedFile::parseSizeToBytes('5MB'));
        $this->assertSame(2 * 1024 * 1024 * 1024, UploadedFile::parseSizeToBytes('2GB'));
        $this->assertSame(500, UploadedFile::parseSizeToBytes(500));
    }
}
