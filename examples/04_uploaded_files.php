<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Validator\ArborValidator;
use Arbor\Validator\AV;
use Arbor\Validator\Files\UploadedFile;

echo "=== Arbor Validator: 04 Real Uploaded Files Validation ===\n\n";

// Enable testing mode to allow testing uploads from CLI / unit tests
ArborValidator::setTestingMode(true);

// 1. Create a dummy test file with REAL JPEG magic bytes: 0xFF 0xD8 0xFF
$tmpFile = tempnam(sys_get_temp_dir(), 'arbor_test_');
file_put_contents($tmpFile, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00`\x00`\x00\x00\xFF\xDB");

// Simulate $_FILES entry
$uploadedFileEntry = [
    'name'     => 'profile_photo.jpg',
    'tmp_name' => $tmpFile,
    'size'     => filesize($tmpFile),
    'error'    => UPLOAD_ERR_OK,
    'type'     => 'image/jpeg', // client-supplied MIME
];

$fileSchema = AV::shape([
    'avatar' => AV::file()
        ->maxSize('5MB')
        ->extension(['jpg', 'jpeg', 'png', 'webp'])
        ->mimeType(['image/jpeg', 'image/png', 'image/webp']),
]);

$result = $fileSchema->safeParse(['avatar' => $uploadedFileEntry]);

echo "1. Validating genuine JPEG file:\n";
echo "   Is Valid: " . ($result->isValid() ? 'YES' : 'NO') . "\n";

if ($result->isValid()) {
    /** @var UploadedFile $file */
    $file = $result->data()['avatar'];
    echo "   Client Filename: " . $file->clientName() . "\n";
    echo "   Client Extension: " . $file->extension() . "\n";
    echo "   Real Magic-Bytes MIME Type: " . $file->mimeType() . "\n";
    echo "   Size: " . $file->size() . " bytes\n";

    // Test moving to destination
    $destination = sys_get_temp_dir() . '/arbor_saved_avatar.jpg';
    $file->moveTo($destination);
    echo "   Moved to: {$destination} (exists: " . (file_exists($destination) ? 'YES' : 'NO') . ")\n";
    @unlink($destination);
}

// 2. Demonstration of spoofed file detection (PHP script renamed to .jpg)
$fakeFile = tempnam(sys_get_temp_dir(), 'arbor_fake_');
file_put_contents($fakeFile, "<?php echo 'malicious code'; ?>");

$spoofedEntry = [
    'name'     => 'exploit.jpg',
    'tmp_name' => $fakeFile,
    'size'     => filesize($fakeFile),
    'error'    => UPLOAD_ERR_OK,
    'type'     => 'image/jpeg', // Fake client MIME
];

$failResult = $fileSchema->safeParse(['avatar' => $spoofedEntry]);

echo "\n2. Spoofed file detection (PHP script disguised as .jpg):\n";
echo "   Failed: " . ($failResult->failed() ? 'YES' : 'NO') . "\n";
echo "   Detected Error: " . $failResult->firstError() . "\n";

@unlink($tmpFile);
@unlink($fakeFile);

echo "\nCompleted successfully.\n";
