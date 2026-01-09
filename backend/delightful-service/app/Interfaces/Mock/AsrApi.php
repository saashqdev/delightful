<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Mock;

use App\Application\Speech\Enum\SandboxAsrStatusEnum;
use App\Domain\Asr\Constants\AsrConfig;
use App\Domain\Asr\Constants\AsrRedisKeys;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * ASR task Mock service
 * 模拟沙箱中的audio合并和 ASR task处理.
 */
class AsrApi
{
    private Redis $redis;

    private LoggerInterface $logger;

    public function __construct(ContainerInterface $container)
    {
        try {
            $this->redis = $container->get(Redis::class);
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
        }
        try {
            $this->logger = $container->get(LoggerFactory::class)->get('MockAsrApi');
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
        }
    }

    /**
     * 启动 ASR task
     * POST /api/v1/sandboxes/{sandboxId}/proxy/api/asr/task/start.
     */
    public function startTask(RequestInterface $request): array
    {
        $sandboxId = $request->route('sandboxId');
        $taskKey = $request->input('task_key', '');
        $sourceDir = $request->input('source_dir', '');
        $workspaceDir = $request->input('workspace_dir', '.workspace');
        $noteFileConfig = $request->input('note_file');
        $transcriptFileConfig = $request->input('transcript_file');

        // recordcalllog
        $this->logger->info('[Mock Sandbox ASR] Start task called', [
            'sandbox_id' => $sandboxId,
            'task_key' => $taskKey,
            'source_dir' => $sourceDir,
            'workspace_dir' => $workspaceDir,
            'note_file_config' => $noteFileConfig,
            'transcript_file_config' => $transcriptFileConfig,
        ]);

        // initializetaskstatus（reset轮询计数）
        $countKey = sprintf(AsrRedisKeys::MOCK_FINISH_COUNT, $taskKey);
        $this->redis->del($countKey);

        return [
            'code' => 1000,
            'message' => 'ASR task started successfully',
            'data' => [
                'status' => SandboxAsrStatusEnum::RUNNING->value,
                'task_key' => $taskKey,
                'source_dir' => $sourceDir,
                'workspace_dir' => $workspaceDir,
                'file_path' => '',
                'duration' => 0,
                'file_size' => 0,
                'error_message' => '',
            ],
        ];
    }

    /**
     * complete ASR task（支持轮询）- V2 结构化版本
     * POST /api/v1/sandboxes/{sandboxId}/proxy/api/asr/task/finish.
     */
    public function finishTask(RequestInterface $request): array
    {
        $sandboxId = $request->route('sandboxId');
        $taskKey = $request->input('task_key', '');
        $workspaceDir = $request->input('workspace_dir', '.workspace');

        // V2 结构化parameter
        $audioConfig = $request->input('audio', []);
        $noteFileConfig = $request->input('note_file');
        $transcriptFileConfig = $request->input('transcript_file');

        // use Redis 计数器模拟轮询进度
        $countKey = sprintf(AsrRedisKeys::MOCK_FINISH_COUNT, $taskKey);
        $count = (int) $this->redis->incr($countKey);
        $this->redis->expire($countKey, AsrConfig::MOCK_POLLING_TTL); // 10分钟过期

        // recordcalllog
        $this->logger->info('[Mock Sandbox ASR] Finish task called (V2)', [
            'sandbox_id' => $sandboxId,
            'task_key' => $taskKey,
            'workspace_dir' => $workspaceDir,
            'audio_config' => $audioConfig,
            'note_file_config' => $noteFileConfig,
            'transcript_file_config' => $transcriptFileConfig,
            'call_count' => $count,
        ]);

        // 前 3 次callreturn finalizing status
        if ($count < 4) {
            return [
                'code' => 1000,
                'message' => 'ASR task is being finalized',
                'data' => [
                    'status' => SandboxAsrStatusEnum::FINALIZING->value,
                    'task_key' => $taskKey,
                ],
            ];
        }

        // 第 4 次callreturn completed status
        $targetDir = $audioConfig['target_dir'] ?? '';
        $outputFilename = $audioConfig['output_filename'] ?? 'audio';

        // 模拟真实沙箱行为：according to output_filename 重命名目录
        // 提取原目录中的time戳部分（格式：_YYYYMMDD_HHMMSS）
        $timestamp = '';
        if (preg_match('/_(\d{8}_\d{6})$/', $targetDir, $matches)) {
            $timestamp = '_' . $matches[1];
        }

        // buildnew目录名：智能标题 + time戳
        $renamedDir = $outputFilename . $timestamp;

        // buildaudiofileinfo
        $audioFileName = $outputFilename . '.webm';
        $audioPath = rtrim($renamedDir, '/') . '/' . $audioFileName;

        // buildreturn数据 (V2 详细版本)
        $responseData = [
            'status' => SandboxAsrStatusEnum::COMPLETED->value,
            'task_key' => $taskKey,
            'intelligent_title' => $outputFilename, // use输出file名作为智能标题
            'error_message' => null,
            'files' => [
                'audio_file' => [
                    'filename' => $audioFileName,
                    'path' => $audioPath, // use重命名后的目录路径
                    'size' => 127569,
                    'duration' => 17.0,
                    'action_performed' => 'merged_and_created',
                    'source_path' => null,
                ],
                'note_file' => null, // default为 null，table示笔记file为空或不存在
            ],
            'deleted_files' => [],
            'operations' => [
                'audio_merge' => 'success',
                'note_process' => 'success',
                'transcript_cleanup' => 'success',
            ],
        ];

        // 如果有笔记fileconfiguration且filesize > 0，添加到return中（模拟真实沙箱的笔记filecontentcheck）
        if ($noteFileConfig !== null && isset($noteFileConfig['target_path'])) {
            // use请求中提供的 target_path，而不是硬编码file名
            // 这样cancorrect支持国际化的file名
            $noteFilePath = $noteFileConfig['target_path'];
            $noteFilename = basename($noteFilePath);

            // 模拟真实沙箱行为：只有当笔记file有content时才return详细info
            // 这里简化处理，default假设有content（真实沙箱willcheckfilecontent是否为空）
            $responseData['files']['note_file'] = [
                'filename' => $noteFilename,
                'path' => $noteFilePath, // use请求中的 target_path
                'size' => 256, // 模拟有content的filesize
                'duration' => null,
                'action_performed' => 'renamed_and_moved',
                'source_path' => $noteFileConfig['source_path'] ?? '',
            ];
        }

        // 如果有stream识别fileconfiguration，recorddelete操作
        if ($transcriptFileConfig !== null && isset($transcriptFileConfig['source_path'])) {
            $responseData['deleted_files'][] = [
                'path' => $transcriptFileConfig['source_path'],
                'action_performed' => 'deleted',
            ];
        }

        return [
            'code' => 1000,
            'message' => 'audio合并已complete',
            'data' => $responseData,
        ];
    }

    /**
     * cancel ASR task
     * POST /api/v1/sandboxes/{sandboxId}/proxy/api/asr/task/cancel.
     */
    public function cancelTask(RequestInterface $request): array
    {
        $sandboxId = $request->route('sandboxId');
        $taskKey = $request->input('task_key', '');
        $workspaceDir = $request->input('workspace_dir', '.workspace');

        // recordcalllog
        $this->logger->info('[Mock Sandbox ASR] Cancel task called', [
            'sandbox_id' => $sandboxId,
            'task_key' => $taskKey,
            'workspace_dir' => $workspaceDir,
        ]);

        // 清理task相关的 Redis status
        $countKey = sprintf(AsrRedisKeys::MOCK_FINISH_COUNT, $taskKey);
        $this->redis->del($countKey);

        return [
            'code' => 1000,
            'message' => 'ASR task canceled successfully',
            'data' => [
                'status' => 'canceled',
                'task_key' => $taskKey,
                'workspace_dir' => $workspaceDir,
            ],
        ];
    }
}
