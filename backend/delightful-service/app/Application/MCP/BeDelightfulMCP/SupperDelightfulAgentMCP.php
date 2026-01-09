<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\MCP\SupperDelightfulMCP;

use App\Application\Contact\UserSetting\UserSettingKey;
use App\Application\Flow\ExecuteManager\NodeRunner\LLM\ToolsExecutor;
use App\Application\MCP\BuiltInMCP\BeDelightfulChat\BeDelightfulChatBuiltInMCPServer;
use App\Application\MCP\Service\MCPServerAppService;
use App\Application\MCP\Utils\MCPServerConfigUtil;
use App\Domain\Agent\Entity\ValueObject\AgentDataIsolation;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Domain\Agent\Service\AgentDomainService;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\MentionType;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulUserSettingDomainService;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowQuery;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use App\Domain\MCP\Entity\MCPServerEntity;
use App\Domain\MCP\Entity\ValueObject\MCPDataIsolation;
use App\Domain\MCP\Entity\ValueObject\Query\MCPServerQuery;
use App\ErrorCode\MCPErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\TempAuth\TempAuthInterface;
use App\Infrastructure\Core\ValueObject\Page;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskContext;
use Hyperf\Codec\Json;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class SupperDelightfulAgentMCP implements SupperDelightfulAgentMCPInterface
{
    protected LoggerInterface $logger;

    public function __construct(
        protected DelightfulUserSettingDomainService $delightfulUserSettingDomainService,
        protected MCPServerAppService $MCPServerAppService,
        protected TempAuthInterface $tempAuth,
        protected AgentDomainService $agentDomainService,
        protected DelightfulFlowDomainService $delightfulFlowDomainService,
        LoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('SupperDelightfulAgentMCP');
    }

    public function createChatMessageRequestMcpConfig(MCPDataIsolation $dataIsolation, TaskContext $taskContext, array $agentIds = [], array $mcpIds = [], array $toolIds = []): ?array
    {
        $mentions = $taskContext->getTask()->getMentions();
        $this->logger->debug('CreateChatMessageRequestMcpConfigArgs', ['mentions' => $mentions, 'agentIds' => $agentIds, 'mcpIds' => $mcpIds, 'toolIds' => $toolIds]);
        try {
            if ($mentions !== null) {
                $mentions = str_replace('\"', '"', $mentions);
                $mentions = Json::decode($mentions);
                foreach ($mentions as $mention) {
                    $type = MentionType::tryFrom($mention['type'] ?? '');
                    switch ($type) {
                        case MentionType::AGENT:
                            if (! empty($mention['agent_id'])) {
                                $agentIds[] = $mention['agent_id'];
                            }
                            break;
                        case MentionType::MCP:
                            if (! empty($mention['id'])) {
                                $mcpIds[] = $mention['id'];
                            }
                            break;
                        case MentionType::TOOL:
                            if (! empty($mention['id'])) {
                                $toolIds[] = $mention['id'];
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
            $agentIds = array_values(array_filter(array_unique($agentIds)));
            $mcpIds = array_values(array_filter(array_unique($mcpIds)));
            $toolIds = array_values(array_filter(array_unique($toolIds)));

            $builtinBeDelightfulServer = BeDelightfulChatBuiltInMCPServer::createByChatParams($dataIsolation, $agentIds, $toolIds);

            $serverOptions = [];
            if ($builtinBeDelightfulServer) {
                $serverOptions[$builtinBeDelightfulServer->getCode()] = $this->createBuiltinBeDelightfulServerOptions($dataIsolation, $agentIds, $toolIds);
            }

            $projectId = $taskContext->getTask()->getProjectId();
            if ($projectId) {
                $projectMcpIds = $this->getProjectMcpServerIds($dataIsolation, (string) $projectId);
                $mcpIds = array_merge($mcpIds, $projectMcpIds);
            }

            $mcpServers = $this->createMcpServers($dataIsolation, $mcpIds, [$builtinBeDelightfulServer], $serverOptions);

            $mcpServers = [
                'mcpServers' => $mcpServers,
            ];
            $this->logger->debug('CreateChatMessageRequestMcpConfig', $mcpServers);
            $taskContext->setMcpConfig($mcpServers);
            return $mcpServers;
        } catch (Throwable $throwable) {
            $this->logger->error('CreateChatMessageRequestMcpConfigError', [
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]);
        }
        return null;
    }

    private function createMcpServers(MCPDataIsolation $mcpDataIsolation, array $mcpIds = [], array $builtinServers = [], array $serverOptions = []): array
    {
        $dataIsolation = DataIsolation::create($mcpDataIsolation->getCurrentOrganizationCode(), $mcpDataIsolation->getCurrentUserId());
        $servers = [];

        $query = new MCPServerQuery();
        $query->setEnabled(true);
        $query->setCodes($mcpIds);
        $data = $this->MCPServerAppService->availableQueries($mcpDataIsolation, $query, Page::createNoPage());
        $mcpServers = $data['list'] ?? [];
        /** @var array<MCPServerEntity> $mcpServers */
        $mcpServers = array_filter(array_merge($mcpServers, $builtinServers), function ($item) {
            return $item instanceof MCPServerEntity;
        });

        $localHttpUrl = config('be-delightful.sandbox.callback_host', '');

        foreach ($mcpServers as $mcpServer) {
            if (! $mcpServer->isBuiltIn() && ! in_array($mcpServer->getCode(), $mcpIds, true)) {
                continue;
            }

            try {
                $mcpServerConfig = MCPServerConfigUtil::create(
                    $mcpDataIsolation,
                    $mcpServer,
                    $localHttpUrl,
                );
                if (! $mcpServerConfig) {
                    ExceptionBuilder::throw(MCPErrorCode::NotFound, 'ServerConfigCreateFailed');
                }
                if (str_starts_with($mcpServerConfig->getUrl(), $localHttpUrl)) {
                    $token = $this->tempAuth->create([
                        'user_id' => $dataIsolation->getCurrentUserId(),
                        'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
                        'server_code' => $mcpServer->getCode(),
                    ], 3600);
                    $mcpServerConfig->setToken($token);
                }
                $config = $mcpServerConfig->toArray();
                $config['server_options'] = $serverOptions[$mcpServer->getCode()] ?? [];
            } catch (Throwable $throwable) {
                $this->logger->notice('CreateChatMessageRequestMcpConfigNotice', [
                    'mcp_server' => [
                        'id' => $mcpServer->getId(),
                        'code' => $mcpServer->getCode(),
                        'name' => $mcpServer->getName(),
                        'description' => $mcpServer->getDescription(),
                    ],
                    'message' => $throwable->getMessage(),
                    'code' => $throwable->getCode(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                ]);
                $config = [
                    'name' => $mcpServer->getName(),
                    'error_message' => $throwable->getMessage(),
                ];
            }

            $servers[$mcpServer->getName()] = $config;
        }
        return $servers;
    }

    /**
     * getproject MCP service器 ID columntable.
     */
    private function getProjectMcpServerIds(MCPDataIsolation $mcpDataIsolation, string $projectId): array
    {
        $dataIsolation = DataIsolation::create($mcpDataIsolation->getCurrentOrganizationCode(), $mcpDataIsolation->getCurrentUserId());
        $mcpServerIds = [];

        $mcpSettings = $this->delightfulUserSettingDomainService->get($dataIsolation, UserSettingKey::genBeDelightfulProjectMCPServers($projectId));
        if ($mcpSettings) {
            $mcpServerIds = array_filter(array_column($mcpSettings->getValue()['servers'], 'id'));
        }
        return $mcpServerIds;
    }

    private function createBuiltinBeDelightfulServerOptions(MCPDataIsolation $dataIsolation, array $agentIds = [], array $toolIds = []): array
    {
        $labelNames = [];

        // query agent information
        $agentDataIsolation = AgentDataIsolation::createByBaseDataIsolation($dataIsolation);
        $agentQuery = new DelightfulAgentQuery();
        $agentQuery->setIds($agentIds);
        $agents = $this->agentDomainService->queries($agentDataIsolation->disabled(), $agentQuery, Page::createNoPage())['list'] ?? [];
        $agentInfos = [];

        // query tool information
        $flowDataIsolation = FlowDataIsolation::createByBaseDataIsolation($dataIsolation);
        $flowQuery = new DelightfulFLowQuery();
        $flowQuery->setCodes($toolIds);
        $tools = ToolsExecutor::getToolFlows($flowDataIsolation->disabled(), $toolIds);

        foreach ($agents as $agent) {
            $agentInfos[$agent->getId()] = [
                'id' => $agent->getId(),
                'name' => $agent->getAgentName(),
                'description' => $agent->getAgentDescription(),
            ];
            $labelNames[] = $agent->getAgentName();
        }
        foreach ($tools as $tool) {
            $labelNames[] = $tool->getName();
        }

        return [
            'label_name' => implode(', ', $labelNames),
            'label_names' => $labelNames,
            'tools' => [
                'call_delightful_agent' => [
                    'label_name' => '',
                    'agents' => $agentInfos,
                ],
            ],
        ];
    }
}
