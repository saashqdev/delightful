<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Driver\TOS;

use BeDelightful\CloudFile\Kernel\Exceptions\CloudFileException;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToWriteFile;
use Throwable;
use Tos\Config\ConfigParser;
use Tos\Model\AppendObjectInput;
use Tos\Model\CopyObjectInput;
use Tos\Model\DeleteObjectInput;
use Tos\Model\GetObjectInput;
use Tos\Model\HeadObjectInput;
use Tos\Model\HeadObjectOutput;
use Tos\Model\PutObjectACLInput;
use Tos\Model\PutObjectInput;
use Tos\TosClient;

class TOSDriver implements FilesystemAdapter
{
    protected TosClient $client;

    protected array $config;

    protected ConfigParser $configParser;

    /**
     * @param array $config = [
     *                      'region' => '',
     *                      'endpoint' => '',
     *                      'bucket' => '',
     *                      'ak' => '',
     *                      'sk' => '',
     *                      'bucket' => '',
     *                      ]
     */
    public function __construct(array $config = [])
    {
        $this->configParser = new ConfigParser($config);
        $this->config = $config;
        $this->client = new TosClient($this->configParser);
    }

    public function fileExists(string $path): bool
    {
        $meta = $this->getMeta($path, false);
        if (! $meta) {
            return false;
        }

        return (bool) $meta->getContentLength();
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $input = new PutObjectInput($this->getBucket(), $path, $contents);
        // Simple way to add configuration
        foreach ($config->get('options', []) as $method => $value) {
            if (method_exists($input, $method)) {
                $input->{$method}($value);
            }
        }
        $this->client->putObject($input);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        if (! is_resource($contents)) {
            throw UnableToWriteFile::atLocation($path, 'The contents is invalid resource.');
        }

        $input = new AppendObjectInput($this->getBucket(), $path);
        // Simple way to add configuration
        foreach ($config->get('options', []) as $method => $value) {
            if (method_exists($input, $method)) {
                $input->{$method}($value);
            }
        }

        // If file size does not exceed the limit (custom defined) 50*1024 * 1024, upload directly
        if (fstat($contents)['size'] <= 50 * 1024 * 1024) {
            $this->write($path, stream_get_contents($contents), $config);
            fclose($contents);
            return;
        }

        // Append uploaded files cannot be copied
        $bufferSize = 1024 * 1024;
        while (! feof($contents)) {
            if (false === $buffer = fread($contents, $bufferSize)) {
                throw UnableToWriteFile::atLocation($path, 'fread failed');
            }
            $input->setContent($buffer);
            $output = $this->client->appendObject($input);
            // Next append upload start position
            $input->setOffset($output->getNextAppendOffset());
        }
        fclose($contents);
    }

    public function read(string $path): string
    {
        $output = $this->client->getObject(new GetObjectInput($this->getBucket(), $path));
        $body = $output->getContent()->getContents();
        $output->getContent()->close();

        return $body;
    }

    public function readStream(string $path)
    {
        $body = $this->read($path);
        $resource = fopen('php://temp', 'r+');
        if ($body !== '') {
            fwrite($resource, $body);
            fseek($resource, 0);
        }

        return $resource;
    }

    public function delete(string $path): void
    {
        $this->client->deleteObject(new DeleteObjectInput($this->getBucket(), $path));
    }

    public function deleteDirectory(string $path): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function createDirectory(string $path, Config $config): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->client->putObjectACL(new PutObjectACLInput($this->getBucket(), $path, $visibility));
    }

    public function visibility(string $path): FileAttributes
    {
        throw new CloudFileException('Not supported yet');
    }

    public function mimeType(string $path): FileAttributes
    {
        $meta = $this->getMeta($path);

        return new FileAttributes($path, null, null, null, $meta->getContentType());
    }

    public function lastModified(string $path): FileAttributes
    {
        $meta = $this->getMeta($path);

        return new FileAttributes($path, null, null, $meta->getLastModified());
    }

    public function fileSize(string $path): FileAttributes
    {
        $meta = $this->getMeta($path);

        return new FileAttributes($path, $meta->getContentLength());
    }

    public function listContents(string $path, bool $deep): iterable
    {
        throw new CloudFileException('Not supported yet');
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $input = new CopyObjectInput($this->getBucket(), $destination, $this->getBucket(), $source);
        // Simple way to add configuration
        foreach ($config->get('options', []) as $method => $value) {
            if (method_exists($input, $method)) {
                $input->{$method}($value);
            }
        }
        $this->client->copyObject($input);
    }

    private function getMeta(string $path, bool $throw = true): ?HeadObjectOutput
    {
        try {
            return $this->client->headObject(new HeadObjectInput($this->getBucket(), $path));
        } catch (Throwable $throwable) {
            if ($throw) {
                throw $throwable;
            }
            return null;
        }
    }

    private function getBucket(): string
    {
        return $this->config['bucket'] ?? '';
    }

    private function getTrn(): string
    {
        return $this->config['trn'] ?? '';
    }
}
