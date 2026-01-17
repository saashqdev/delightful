<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AbstractSandboxOS;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrAudioConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrNoteFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrTranscriptFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Response\AsrRecorderResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Constants\SandboxEndpoints;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Exception;
use Hyperf\Logger\LoggerFactory;

/**
 * ASR recording service implementation.
 */
class AsrRecorderService extends AbstractSandboxOS implements AsrRecorderInterface
{
    public function __construct(
        LoggerFactory $loggerFactory,
        private readonly SandboxGatewayInterface $gateway
    ) {
        parent::__construct($loggerFactory);
    }

    public function startTask(
        string $sandboxId,
        string $taskKey,
        string $sourceDir,
        string $workspaceDir = '.workspace',
        ?AsrNoteFileConfig $noteFileConfig = null,
        ?AsrTranscriptFileConfig $transcriptFileConfig = null
    ): AsrRecorderResponse {
        $requestData = [
            'task_key' => $taskKey,
            'source_dir' => $sourceDir,
            'workspace_dir' => $workspaceDir,
        ];

        // Add note file config (start phase only sends source_path)
        if ($noteFileConfig !== null) {
            $requestData['note_file'] = [
                'source_path' => $noteFileConfig->getSourcePath(),
            ];
        }

        // Add streaming transcript file config (start phase only sends source_path)
        if ($transcriptFileConfig !== null) {
            $requestData['transcript_file'] = [
                'source_path' => $transcriptFileConfig->getSourcePath(),
            ];
        }

        try {
            $this->logger->info('ASR Recorder: Starting task', [
                'sandbox_id' => $sandboxId,
                'task_key' => $taskKey,
                'source_dir' => $sourceDir,
                'workspace_dir' => $workspaceDir,
                'note_file_source_path' => $noteFileConfig?->getSourcePath(),
                'transcript_file_source_path' => $transcriptFileConfig?->getSourcePath(),
            ]);

            // Call sandbox API
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                SandboxEndpoints::ASR_TASK_START,
                $requestData
            );

            $response = AsrRecorderResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('ASR Recorder: Task started successfully', [
                    'sandbox_id' => $sandboxId,
                    'task_key' => $taskKey,
                    'status' => $response->getStatus(),
                ]);
            } else {
                $this->logger->error('ASR Recorder: Failed to start task', [
                    'sandbox_id' => $sandboxId,
                    'task_key' => $taskKey,
                    'code' => $response->code,
                    'message' => $response->message,
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('ASR Recorder: Unexpected error during start task', [
                'sandbox_id' => $sandboxId,
                'task_key' => $taskKey,
                'error' => $e->getMessage(),
            ]);

            return AsrRecorderResponse::fromApiResponse([
                'code' => -1,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function finishTask(
        string $sandboxId,
        string $taskKey,
        string $workspaceDir,
        AsrAudioConfig $audioConfig,
        ?AsrNoteFileConfig $noteFileConfig = null,
        ?AsrTranscriptFileConfig $transcriptFileConfig = null
    ): AsrRecorderResponse {
        // Build request data (V2 structured version)
        $requestData = [
            'task_key' => $taskKey,
            'workspace_dir' => $workspaceDir,
            'audio' => $audioConfig->toArray(),
        ];

        // Add note file config
        if ($noteFileConfig !== null) {
            $requestData['note_file'] = $noteFileConfig->toArray();
        }

        // Add streaming transcript file config
        if ($transcriptFileConfig !== null) {
            $requestData['transcript_file'] = $transcriptFileConfig->toArray();
        }

        try {
            $this->logger->info('ASR Recorder: Finishing task (V2)', [
                'sandbox_id' => $sandboxId,
                'task_key' => $taskKey,
                'workspace_dir' => $workspaceDir,
                'audio_config' => $audioConfig->toArray(),
                'note_file_config' => $noteFileConfig?->toArray(),
                'transcript_file_config' => $transcriptFileConfig?->toArray(),
            ]);

            // Call sandbox API
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                SandboxEndpoints::ASR_TASK_FINISH,
                $requestData
            );

            $response = AsrRecorderResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('ASR Recorder: Task finish request successful', [
                    'sandbox_id' => $sandboxId,
                    'task_key' => $taskKey,
                    'status' => $response->getStatus(),
                    'file_path' => $response->getFilePath(),
                ]);
            } else {
                $this->logger->error('ASR Recorder: Failed to finish task', [
                    'sandbox_id' => $sandboxId,
                    'task_key' => $taskKey,
                    'code' => $response->code,
                    'message' => $response->message,
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('ASR Recorder: Unexpected error during finish task', [
                'sandbox_id' => $sandboxId,
                'task_key' => $taskKey,
                'error' => $e->getMessage(),
            ]);

            return AsrRecorderResponse::fromApiResponse([
                'code' => -1,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function cancelTask(
        string $sandboxId,
        string $taskKey,
        string $workspaceDir = '.workspace'
    ): AsrRecorderResponse {
        $requestData = [
            'task_key' => $taskKey,
            'workspace_dir' => $workspaceDir,
        ];

        try {
            $this->logger->info('ASR Recorder: Canceling task', [
                'sandbox_id' => $sandboxId,
                'task_key' => $taskKey,
                'workspace_dir' => $workspaceDir,
            ]);

            // Call sandbox API
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                SandboxEndpoints::ASR_TASK_CANCEL,
                $requestData
            );

            $response = AsrRecorderResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('ASR Recorder: Task canceled successfully', [
                    'sandbox_id' => $sandboxId,
                    'task_key' => $taskKey,
                    'status' => $response->getStatus(),
                ]);
            } else {
                $this->logger->error('ASR Recorder: Failed to cancel task', [
                    'sandbox_id' => $sandboxId,
                    'task_key' => $taskKey,
                    'code' => $response->code,
                    'message' => $response->message,
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('ASR Recorder: Unexpected error during cancel task', [
                'sandbox_id' => $sandboxId,
                'task_key' => $taskKey,
                'error' => $e->getMessage(),
            ]);

            return AsrRecorderResponse::fromApiResponse([
                'code' => -1,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }
}
