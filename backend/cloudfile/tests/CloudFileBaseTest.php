<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Tests;

use BeDelightful\CloudFile\CloudFile;
use BeDelightful\CloudFile\Kernel\Exceptions\CloudFileException;
use BeDelightful\CloudFile\Kernel\FilesystemProxy;
use BeDelightful\SdkBase\SdkBase;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
abstract class CloudFileBaseTest extends TestCase
{
    public function setUp(): void
    {
        error_reporting(E_ALL ^ E_DEPRECATED);
    }

    public function testCreateCloudFile()
    {
        $cloudFile = $this->createCloudFile();
        $this->assertInstanceOf(CloudFile::class, $cloudFile);
    }

    protected function createCloudFile(): CloudFile
    {
        // If you want to test, you need to fill in the corresponding configuration with real values, no mock is done here
        $configs = [
            'storages' => json_decode(file_get_contents(__DIR__ . '/../storages.json'), true)['storages'] ?? [],
        ];

        $container = new SdkBase(new Container(), [
            'sdk_name' => 'easy_file_sdk',
            'exception_class' => CloudFileException::class,
            'cloudfile' => $configs,
        ]);

        return new CloudFile($container);
    }

    /**
     * Get filesystem instance for testing.
     * Subclasses should implement getStorageName() to define which storage config to use.
     */
    protected function getFilesystem(): FilesystemProxy
    {
        try {
            $easyFile = $this->createCloudFile();
            return $easyFile->get($this->getStorageName());
        } catch (Exception $e) {
            $this->skipTestDueToMissingConfig($this->getStorageName() . ' configuration not available: ' . $e->getMessage());
        }
    }

    /**
     * Get the storage configuration name for this test class.
     * Must be implemented by subclasses.
     */
    abstract protected function getStorageName(): string;

    /**
     * Skip test due to missing configuration.
     *
     * @return never
     */
    protected function skipTestDueToMissingConfig(string $message): void
    {
        $this->markTestSkipped($message);
    }
}
