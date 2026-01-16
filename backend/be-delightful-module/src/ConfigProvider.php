<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful;

use App\Domain\Chat\DTO\Message\ChatMessage\SuperAgentMessageInterface;
use App\Domain\Chat\Event\Agent\AgentExecuteInterface;
use Delightful\BeDelightful\Application\Share\Adapter\TopicShareableResource;
use Delightful\BeDelightful\Application\Share\Factory\ShareableResourceFactory;
use Delightful\BeDelightful\Application\Share\Service\ResourceShareAppService;
use Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe\ProjectOperatorLogSubscriber;
use Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe\SuperAgentMessageSubscriberV2;
use Delightful\BeDelightful\Application\SuperAgent\Service\AgentAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\FileProcessAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\HandleAgentMessageAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\MessageQueueAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\MessageScheduleAppService;
use Delightful\BeDelightful\Domain\Agent\Repository\Facade\BeDelightfulAgentRepositoryInterface;
use Delightful\BeDelightful\Domain\Agent\Repository\Persistence\BeDelightfulAgentRepository;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\SuperAgentMessage;
use Delightful\BeDelightful\Domain\Share\Repository\Facade\ResourceShareRepositoryInterface;
use Delightful\BeDelightful\Domain\Share\Repository\Persistence\ResourceShareRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\MessageQueueRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\MessageScheduleLogRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\MessageScheduleRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectForkRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectMemberRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectMemberSettingRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectOperationLogRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskFileCleanupRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskFileRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskFileVersionRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TokenUsageRecordRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\WorkspaceRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\WorkspaceVersionRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\MessageQueueRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\MessageScheduleLogRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\MessageScheduleRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\ProjectForkRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\ProjectMemberRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\ProjectMemberSettingRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\ProjectOperationLogRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\ProjectRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\TaskFileCleanupRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\TaskFileRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\TaskFileVersionRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\TaskMessageRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\TaskRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\TokenUsageRecordRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\TopicRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\WorkspaceRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence\WorkspaceVersionRepository;
use Delightful\BeDelightful\Domain\SuperAgent\Service\MessageScheduleDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectOperationLogDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileVersionDomainService;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\SandboxInterface;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Volcengine\SandboxService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\SandboxAgentInterface;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\SandboxAgentService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\AsrRecorderInterface;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\AsrRecorderService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\FileConverterInterface;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\FileConverterService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayService;
use Delightful\BeDelightful\Listener\AddRouteListener;
use Delightful\BeDelightful\Listener\I18nLoadListener;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ConfigProvider
{
    public function __invoke(): array
    {
        define('SUPER_MAGIC_MODULE_PATH', BASE_PATH . '/vendor/dtyq/be-delightful-module');

        $publishConfigs = [];

        // 遍历 publish/route 文件夹下的所有文件
        $routeDir = __DIR__ . '/../publish/route/';
        if (is_dir($routeDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($routeDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = $file->getSubPath() . '/' . $file->getFilename();
                    $publishConfigs[] = [
                        'id' => 'route_' . str_replace('/', '_', $relativePath),
                        'description' => 'Route file: ' . $relativePath,
                        'source' => $file->getPathname(),
                        'destination' => BASE_PATH . '/config/' . $relativePath,
                    ];
                }
            }
        }

        return [
            'dependencies_priority' => [
                // 助理执行事件
                AgentExecuteInterface::class => SuperAgentMessageSubscriberV2::class,
                SuperAgentMessageInterface::class => SuperAgentMessage::class,
            ],
            'dependencies' => [
                // 添加接口到实现类的映射
                TaskFileRepositoryInterface::class => TaskFileRepository::class,
                TaskFileCleanupRepositoryInterface::class => TaskFileCleanupRepository::class,
                TaskFileVersionRepositoryInterface::class => TaskFileVersionRepository::class,
                TopicRepositoryInterface::class => TopicRepository::class,
                TaskRepositoryInterface::class => TaskRepository::class,
                WorkspaceRepositoryInterface::class => WorkspaceRepository::class,
                TaskMessageRepositoryInterface::class => TaskMessageRepository::class,
                ProjectRepositoryInterface::class => ProjectRepository::class,
                ProjectOperationLogRepositoryInterface::class => ProjectOperationLogRepository::class,
                ProjectOperationLogDomainService::class => ProjectOperationLogDomainService::class,
                SandboxInterface::class => SandboxService::class,
                ProjectMemberRepositoryInterface::class => ProjectMemberRepository::class,
                ProjectMemberSettingRepositoryInterface::class => ProjectMemberSettingRepository::class,
                // 添加SandboxOS相关服务的依赖注入
                SandboxGatewayInterface::class => SandboxGatewayService::class,
                SandboxAgentInterface::class => SandboxAgentService::class,
                FileConverterInterface::class => FileConverterService::class,
                AsrRecorderInterface::class => AsrRecorderService::class,
                AgentAppService::class => AgentAppService::class,
                // 添加FileProcessAppService的依赖注入
                FileProcessAppService::class => FileProcessAppService::class,
                // 添加HandleAgentMessageAppService的依赖注入
                HandleAgentMessageAppService::class => HandleAgentMessageAppService::class,
                // 添加MessageQueueAppService的依赖注入
                MessageQueueAppService::class => MessageQueueAppService::class,
                // 添加MessageScheduleAppService的依赖注入
                MessageScheduleAppService::class => MessageScheduleAppService::class,
                // 添加分享相关服务
                ShareableResourceFactory::class => ShareableResourceFactory::class,
                TopicShareableResource::class => TopicShareableResource::class,
                ResourceShareRepositoryInterface::class => ResourceShareRepository::class,
                ResourceShareAppService::class => ResourceShareAppService::class,
                TokenUsageRecordRepositoryInterface::class => TokenUsageRecordRepository::class,
                WorkspaceVersionRepositoryInterface::class => WorkspaceVersionRepository::class,
                ProjectForkRepositoryInterface::class => ProjectForkRepository::class,
                MessageQueueRepositoryInterface::class => MessageQueueRepository::class,
                MessageScheduleLogRepositoryInterface::class => MessageScheduleLogRepository::class,
                MessageScheduleRepositoryInterface::class => MessageScheduleRepository::class,

                // agent 管理
                BeDelightfulAgentRepositoryInterface::class => BeDelightfulAgentRepository::class,
                TaskFileVersionDomainService::class => TaskFileVersionDomainService::class,
                MessageScheduleDomainService::class => MessageScheduleDomainService::class,
            ],
            'listeners' => [
                AddRouteListener::class,
                I18nLoadListener::class,
                ProjectOperatorLogSubscriber::class,
            ],
            'error_message' => [
                'error_code_mapper' => [
                    SuperAgentErrorCode::class => [51000, 51299],
                    ShareErrorCode::class => [51300, 51400],
                ],
            ],
            'commands' => [],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => $publishConfigs,
        ];
    }

    public function getRoutes(): array
    {
        return [
            'routes' => [
                'path' => __DIR__ . '/../publish/route',
            ],
        ];
    }
}
