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
 * 模拟沙箱middle的audiomerge和 ASR taskprocess.
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
     * start ASR task
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

        // initializetaskstatus（resetround询计数）
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
     * complete ASR task（supportround询）- V2 结构化version
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

        // use Redis 计数器模拟round询进degree
        $countKey = sprintf(AsrRedisKeys::MOCK_FINISH_COUNT, $taskKey);
        $count = (int) $this->redis->incr($countKey);
        $this->redis->expire($countKey, AsrConfig::MOCK_POLLING_TTL); // 10minute钟expire

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

        // front 3 timecallreturn finalizing status
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

        // the 4 timecallreturn completed status
        $targetDir = $audioConfig['target_dir'] ?? '';
        $outputFilename = $audioConfig['output_filename'] ?? 'audio';

        // 模拟真实沙箱line为：according to output_filename 重命名directory
        // 提取原directorymiddle的time戳部minute（format：_YYYYMMDD_HHMMSS）
        $timestamp = '';
        if (preg_match('/_(\d{8}_\d{6})$/', $targetDir, $matches)) {
            $timestamp = '_' . $matches[1];
        }

        // buildnewdirectory名：智能title + time戳
        $renamedDir = $outputFilename . $timestamp;

        // buildaudiofileinfo
        $audioFileName = $outputFilename . '.webm';
        $audioPath = rtrim($renamedDir, '/') . '/' . $audioFileName;

        // buildreturndata (V2 详细version)
        $responseData = [
            'status' => SandboxAsrStatusEnum::COMPLETED->value,
            'task_key' => $taskKey,
            'intelligent_title' => $outputFilename, // useoutputfile名作为智能title
            'error_message' => null,
            'files' => [
                'audio_file' => [
                    'filename' => $audioFileName,
                    'path' => $audioPath, // use重命名back的directorypath
                    'size' => 127569,
                    'duration' => 17.0,
                    'action_performed' => 'merged_and_created',
                    'source_path' => null,
                ],
                'note_file' => null, // default为 null，table示笔记file为空ornot存in
            ],
            'deleted_files' => [],
            'operations' => [
                'audio_merge' => 'success',
                'note_process' => 'success',
                'transcript_cleanup' => 'success',
            ],
        ];

        // ifhave笔记fileconfigurationandfilesize > 0，添加toreturnmiddle（模拟真实沙箱的笔记filecontentcheck）
        if ($noteFileConfig !== null && isset($noteFileConfig['target_path'])) {
            // userequestmiddle提供的 target_path，而not是硬encodingfile名
            // 这样cancorrectsupport国际化的file名
            $noteFilePath = $noteFileConfig['target_path'];
            $noteFilename = basename($noteFilePath);

            // 模拟真实沙箱line为：onlywhen笔记filehavecontento clock才return详细info
            // 这withinsimplifyprocess，default假设havecontent（真实沙箱willcheckfilecontentwhether为空）
            $responseData['files']['note_file'] = [
                'filename' => $noteFilename,
                'path' => $noteFilePath, // userequestmiddle的 target_path
                'size' => 256, // 模拟havecontent的filesize
                'duration' => null,
                'action_performed' => 'renamed_and_moved',
                'source_path' => $noteFileConfig['source_path'] ?? '',
            ];
        }

        // ifhavestream识别fileconfiguration，recorddelete操作
        if ($transcriptFileConfig !== null && isset($transcriptFileConfig['source_path'])) {
            $responseData['deleted_files'][] = [
                'path' => $transcriptFileConfig['source_path'],
                'action_performed' => 'deleted',
            ];
        }

        return [
            'code' => 1000,
            'message' => 'audiomerge已complete',
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
