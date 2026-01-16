<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\SaveFilesRequest;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\SandboxAgentInterface;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Exception\SandboxOperationException;
use BeDelightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class BeDelightfulDomainService
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerFactory $loggerFactory,
        private readonly SandboxAgentInterface $agent,
    ) {
        $this->logger = $loggerFactory->get('sandbox');
    }

    public function saveFileData(string $sandboxId, array $fileDataList, string $workDir): array
    {
        $this->logger->info('[BeDelightful][App] Save file data', [
            'sandbox_id' => $sandboxId,
            'file_data_list' => $fileDataList,
            'work_dir' => $workDir,
        ]);
        $files = [];
        foreach ($fileDataList as $fileData) {
            $files[] = [
                'file_key' => $fileData['file_key'],
                'file_path' => WorkDirectoryUtil::getRelativeFilePath($fileData['file_key'], $workDir),
                'content' => $fileData['content'],
                'is_encrypted' => $fileData['is_encrypted'],
            ];
        }

        $request = SaveFilesRequest::create($files);
        $response = $this->agent->saveFiles($sandboxId, $request);

        if (! $response->isSuccess()) {
            throw new SandboxOperationException(
                'Save files via sandbox',
                $response->getMessage(),
                $response->getCode()
            );
        }

        return $response->getData();
    }
}
