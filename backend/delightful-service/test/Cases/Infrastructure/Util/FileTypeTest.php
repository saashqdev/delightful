<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Infrastructure\Util;

use App\Infrastructure\Util\FileType;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class FileTypeTest extends TestCase
{
    /**
     * Test getting file type from local file.
     */
    public function testGetTypeFromLocalFile()
    {
        // Create a temporary text file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'Hello, World!');

        try {
            // Test text file
            $fileType = FileType::getType($tempFile);
            $this->assertEquals('txt', $fileType, 'Text file should be recognized as txt');
        } finally {
            // Clean up temporary file
            @unlink($tempFile);
        }
    }

    /**
     * Test getting file type from URL path.
     */
    public function testGetTypeFromUrlPath()
    {
        // Test URLs with different extensions
        $pdfUrl = 'https://example.com/documents/sample.pdf?param=value';
        $fileType = FileType::getType($pdfUrl);
        $this->assertEquals('pdf', $fileType, 'PDF URL should recognize pdf extension');

        $jpgUrl = 'https://example.com/images/photo.jpg';
        $fileType = FileType::getType($jpgUrl);
        $this->assertEquals('jpg', $fileType, 'JPG URL should recognize jpg extension');

        $docxUrl = 'https://example.com/files/document.docx#section1';
        $fileType = FileType::getType($docxUrl);
        $this->assertEquals('docx', $fileType, 'DOCX URL should recognize docx extension');
    }

    /**
     * Test using publicly accessible image URL.
     */
    public function testRealImageUrl()
    {
        // Test using an actual accessible image URL
        $imageUrl = 'https://www.php.net/images/logos/php-logo.svg';
        $fileType = FileType::getType($imageUrl);
        $this->assertEquals('svg', $fileType, 'Should correctly recognize SVG file');
    }

    /**
     * Test .php-cs-fixer.php file in project.
     */
    public function testProjectPhpCsFixerFile()
    {
        // Get project root directory
        $projectRoot = dirname(__DIR__, 4);

        // Test .php-cs-fixer.php file
        $phpCsFixerFile = $projectRoot . '/.php-cs-fixer.php';

        // Ensure file exists
        $this->assertFileExists($phpCsFixerFile, '.php-cs-fixer.php file does not exist');

        // Get file type and verify
        $fileType = FileType::getType($phpCsFixerFile);
        $this->assertEquals('php', $fileType, 'Should be recognized as PHP file');

        // Verify file content contains specific text to confirm it's the correct file
        $content = file_get_contents($phpCsFixerFile);
        $this->assertStringContainsString('PhpCsFixer\Config', $content, 'File content should contain PhpCsFixer\Config');
    }

    /**
     * testfromHTTPheadinfogettype(needmockHTTPresponse).
     *
     * notice:thistestmaybeneedusefunctionmock,ifprojectmiddlenothaveconfigurationfunctionmock,
     * canwillthistestmarkforskiporusetrueactualURLconducttest
     */
    public function testGetTypeFromHeaders()
    {
        // markthistestforskip,因forneedmockalllocalfunction
        $this->markTestSkipped('needfunctionmockfeature才cancompletetest');
    }

    /**
     * testnomethodidentifyfiletypeo clockthrowexception.
     * sameneedfunctionmocksupport
     */
    public function testInvalidFileType()
    {
        $this->markTestSkipped('needfunctionmockfeature才cancompletetest');
    }

    /**
     * testfiletoobigsituation.
     */
    public function testFileTooLarge()
    {
        $this->markTestSkipped('needfunctionmockfeature才cancompletetest');
    }
}
