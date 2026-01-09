<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Mock;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Constant\WorkspaceStatus;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * sandboxmanage Mock service
 * mocksandboxcreate,statusquery,workregionstatusetcmanageinterface.
 */
class SandboxApi
{
    private LoggerInterface $logger;

    public function __construct(ContainerInterface $container)
    {
        try {
            $this->logger = $container->get(LoggerFactory::class)->get('MockSandboxApi');
        } catch (ContainerExceptionInterface|NotFoundExceptionInterface) {
        }
    }

    /**
     * querysandboxstatus
     * GET /api/v1/sandboxes/{sandboxId}.
     */
    public function getSandboxStatus(RequestInterface $request): array
    {
        $sandboxId = $request->route('sandboxId');

        $this->logger->info('[Mock Sandbox] Get sandbox status', [
            'sandbox_id' => $sandboxId,
        ]);

        // mocksandboxalreadyexistsinandrunlinemiddle
        return [
            'code' => 1000,
            'message' => 'Success',
            'data' => [
                'sandbox_id' => $sandboxId,
                'status' => SandboxStatus::RUNNING,
                'project_id' => 'mock_project_id',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * createsandbox
     * POST /api/v1/sandboxes.
     */
    public function createSandbox(RequestInterface $request): array
    {
        $projectId = $request->input('project_id', '');
        $sandboxId = $request->input('sandbox_id', '');
        $projectOssPath = $request->input('project_oss_path', '');

        $this->logger->info('[Mock Sandbox] Create sandbox', [
            'project_id' => $projectId,
            'sandbox_id' => $sandboxId,
            'project_oss_path' => $projectOssPath,
        ]);

        // mocksandboxcreatesuccess
        return [
            'code' => 1000,
            'message' => 'Sandbox created successfully',
            'data' => [
                'sandbox_id' => $sandboxId,
                'status' => SandboxStatus::RUNNING,
                'project_id' => $projectId,
                'project_oss_path' => $projectOssPath,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * getworkregionstatus
     * GET /api/v1/sandboxes/{sandboxId}/proxy/api/v1/workspace/status.
     */
    public function getWorkspaceStatus(RequestInterface $request): array
    {
        $sandboxId = $request->route('sandboxId');

        $this->logger->info('[Mock Sandbox] Get workspace status', [
            'sandbox_id' => $sandboxId,
        ]);

        // mockworkregionthen绪status
        // notice:status mustreturnintegertype,toshould WorkspaceStatus constant
        return [
            'code' => 1000,
            'message' => 'success',
            'data' => [
                'status' => WorkspaceStatus::READY, // initializecomplete,workregion completeallcanuse
                'sandbox_id' => $sandboxId,
                'workspace_path' => '/workspace',
                'is_ready' => true,
            ],
        ];
    }

    /**
     * initialize Agent
     * POST /api/v1/sandboxes/{sandboxId}/proxy/api/v1/messages/chat.
     */
    public function initAgent(RequestInterface $request): array
    {
        $sandboxId = $request->route('sandboxId');
        $userId = $request->input('user_id', '');
        $taskMode = $request->input('task_mode', '');
        $agentMode = $request->input('agent_mode', '');
        $modelId = $request->input('model_id', '');

        $this->logger->info('[Mock Sandbox Agent] Initialize agent called', [
            'sandbox_id' => $sandboxId,
            'user_id' => $userId,
            'task_mode' => $taskMode,
            'agent_mode' => $agentMode,
            'model_id' => $modelId,
        ]);

        return [
            'code' => 1000,
            'message' => 'success',
            'data' => [
                'agent_id' => 'mock_agent_' . uniqid(),
                'status' => 'initialized',
                'message_id' => 'mock_msg_' . uniqid(),
                'sandbox_id' => $sandboxId,
            ],
        ];
    }

    /**
     * initializesandbox(simplifyversion,useat ASR etcnochatmessagescenario)
     * POST /api/v1/sandboxes/{sandboxId}/proxy/v1/messages/chat.
     *
     * requestbodyexample:
     * {
     *   "message_id": "asr_init_sandbox_001_1234567890",
     *   "type": "init",
     *   "metadata": {
     *     "sandbox_id": "sandbox_001",
     *     "user_id": "user_123",
     *     "organization_code": "org_001",
     *     "be_delightful_task_id": "",
     *     "language": "zh_CN"
     *   }
     * }
     */
    public function initSandbox(RequestInterface $request): array
    {
        $sandboxId = $request->route('sandboxId');
        $messageId = $request->input('message_id', '');
        $type = $request->input('type', '');
        $metadata = $request->input('metadata', []);

        $this->logger->info('[Mock Sandbox] Initialize sandbox called', [
            'sandbox_id' => $sandboxId,
            'message_id' => $messageId,
            'type' => $type,
            'metadata' => $metadata,
        ]);

        // verifyrequired parameterparameter
        if (empty($type) || $type !== 'init') {
            return [
                'code' => 4000,
                'message' => 'Invalid type, must be "init"',
                'data' => null,
            ];
        }

        if (empty($metadata['sandbox_id']) || empty($metadata['user_id']) || empty($metadata['organization_code'])) {
            return [
                'code' => 4000,
                'message' => 'Missing required metadata fields: sandbox_id, user_id, organization_code',
                'data' => null,
            ];
        }

        // mocksandboxinitializesuccessresponse
        return [
            'code' => 1000,
            'message' => 'workregioninitializesuccess',
            'data' => null,
        ];
    }
}
