<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\MCP\BuiltInMCP\BeDelightfulChat;

use App\Application\Flow\ExecuteManager\NodeRunner\LLM\ToolsExecutor;
use App\Application\Flow\Service\DelightfulFlowExecuteAppService;
use App\Application\Permission\Service\OperationPermissionAppService;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Chat\Entity\ValueObject\InstructionType;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\MCP\Entity\ValueObject\MCPDataIsolation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\ErrorCode\MCPErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Flow\DTO\DelightfulFlowApiChatDTO;
use Delightful\PhpMcp\Server\FastMcp\Tools\RegisteredTool;
use Delightful\PhpMcp\Types\Tools\Tool;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;

class BeDelightfulChatManager
{
    private const string REDIS_KEY_PREFIX = 'be_delightful_chat_manager:';

    private const int REDIS_KEY_TTL = 7200;

    public static function createByChatParams(MCPDataIsolation $MCPDataIsolation, string $mcpServerCode, array $agentIds = [], array $toolIds = []): void
    {
        $redis = self::getRedis();
        $key = self::buildRedisKey($mcpServerCode);

        $data = [
            'organization_code' => $MCPDataIsolation->getCurrentOrganizationCode(),
            'user_id' => $MCPDataIsolation->getCurrentUserId(),
            'agent_ids' => $agentIds,
            'tool_ids' => $toolIds,
            'created_at' => time(),
        ];

        $redis->setex($key, self::REDIS_KEY_TTL, json_encode($data));
    }

    public static function getRegisteredTools(string $mcpServerCode): array
    {
        $redis = self::getRedis();
        $key = self::buildRedisKey($mcpServerCode);

        $data = $redis->get($key);

        if (! $data) {
            return [];
        }

        $decodedData = json_decode($data, true);

        if (! $decodedData || ! is_array($decodedData)) {
            return [];
        }

        $organizationCode = $decodedData['organization_code'] ?? '';
        $userId = $decodedData['user_id'] ?? '';
        $flowDataIsolation = FlowDataIsolation::create($organizationCode, $userId);

        $agents = self::getAgents($flowDataIsolation, $decodedData['agent_ids'] ?? []);
        $tools = self::getTools($flowDataIsolation, $decodedData['tool_ids'] ?? []);

        return array_merge($tools, $agents);
    }

    /**
     * @return array<RegisteredTool>
     */
    private static function getAgents(FlowDataIsolation $flowDataIsolation, array $agentIds): array
    {
        // 1. query所有可用 agent
        $agents = di(DelightfulAgentDomainService::class)->getAgentByIds($agentIds);

        // 如果没有可用的 agents，直接return空array
        if (empty($agents)) {
            return [];
        }

        $hasAgents = false;
        $allInstructions = [];

        // 2. generate一份大modelcall工具可阅读的description
        $description = <<<'MARKDOWN'
call麦吉 AI 助理进行conversation

可用的 AI 助理list：

MARKDOWN;

        foreach ($agents as $agent) {
            if (! $agent->isAvailable()) {
                continue;
            }
            $instruction = $agent->getInstructs();
            $instructionDescription = self::parseInstructionDescription($instruction);
            $description .= sprintf(
                "• ID: %s\n  name: %s\n  description: %s%s\n\n",
                $agent->getId(),
                $agent->getAgentName(),
                $agent->getAgentDescription() ?: '暂无description',
                $instructionDescription ? "\n  可用指令: {$instructionDescription}" : ''
            );

            // 收集所有指令info用于generate schema
            if ($instruction) {
                $allInstructions[$agent->getId()] = $instruction;
            }

            $hasAgents = true;
        }

        $usageInstructions = <<<'MARKDOWN'
use说明：
• must提供 agent_id 和 message parameter
• conversation_id 用于保持conversation连续性，sameID的messagewill共享上下文

MARKDOWN;

        $description .= $usageInstructions;

        // 添加指令parameter说明
        if (! empty($allInstructions)) {
            $instructionHelp = <<<'MARKDOWN'
指令parameter instruction（可选）：
• format：[{"name": "指令name", "value": "指令value"}, ...]
• 单选type：从可选value中选择一个，for example "yes", "no"
• 开关type：只能是 "on" 或 "off"
• 如果不提供指令parameter，将usedefaultvalue

call示例：
```json
{
  "agent_id": "123456",
  "message": "你好，请帮我分析一下...",
  "conversation_id": "conv_001",
  "instruction": [
    {"name": "开关", "value": "on"},
    {"name": "ok", "value": "yes"}
  ]
}
```

MARKDOWN;

            $description .= $instructionHelp;
        }

        if (! $hasAgents) {
            return [];
        }

        // generate指令的 JSON Schema
        $instructionSchema = self::generateInstructionSchema($allInstructions);

        $registeredAgent = new RegisteredTool(
            tool: new Tool(
                name: 'call_delightful_agent',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'agent_id' => [
                            'type' => 'string',
                            'description' => '要call的 AI 助理 ID',
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => 'send给 AI 助理的messagecontent',
                        ],
                        'conversation_id' => [
                            'type' => 'string',
                            'description' => 'sessionID，用于记忆功能，samesessionID的message将具有共享的上下文',
                        ],
                        'instruction' => $instructionSchema,
                    ],
                    'required' => ['agent_id', 'message'],
                    'additionalProperties' => false,
                ],
                description: $description,
            ),
            callable: function (array $arguments) use ($flowDataIsolation) {
                $agentId = $arguments['agent_id'] ?? null;
                if (! $agentId) {
                    ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.required', ['label' => 'agent_id']);
                }
                $message = $arguments['message'] ?? null;
                if (! $message) {
                    ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.required', ['label' => 'message']);
                }
                $agent = di(DelightfulAgentDomainService::class)->getAgentById($agentId);
                if (! $agent) {
                    ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.not_found', ['label' => $agentId]);
                }
                $apiChatDTO = new DelightfulFlowApiChatDTO();
                $apiChatDTO->setFlowCode($agent->getFlowCode());
                $apiChatDTO->setMessage($message);
                $apiChatDTO->setConversationId($arguments['conversation_id'] ?? '');
                $apiChatDTO->setInstruction($arguments['instruction'] ?? []);
                return di(DelightfulFlowExecuteAppService::class)->apiChatByMCPTool($flowDataIsolation, $apiChatDTO);
            },
        );
        return [$registeredAgent];
    }

    /**
     * Parse instruction data to generate description text.
     */
    private static function parseInstructionDescription(?array $instructions): string
    {
        if (empty($instructions)) {
            return '';
        }

        $descriptions = [];
        foreach ($instructions as $group) {
            if (empty($group['items'])) {
                continue;
            }

            foreach ($group['items'] as $item) {
                if (empty($item['name']) || ($item['hidden'] ?? false)) {
                    continue;
                }

                // Only process items with instruction_type = 1
                if (($item['instruction_type'] ?? null) !== InstructionType::Flow->value) {
                    continue;
                }

                $baseDescription = $item['name'];

                // Add description if exists
                if (! empty($item['description'])) {
                    $baseDescription .= "({$item['description']})";
                }

                if ($item['type'] === 1) {
                    // Single selection
                    $values = array_column($item['values'] ?? [], 'value');
                    if (! empty($values)) {
                        $descriptions[] = "{$baseDescription}[单选: " . implode(' | ', $values) . ']';
                    }
                } elseif ($item['type'] === 2) {
                    // Switch
                    $defaultValue = $item['default_value'] ?? 'off';
                    $descriptions[] = "{$baseDescription}[开关: on/off, default:{$defaultValue}]";
                }
            }
        }

        return implode(', ', $descriptions);
    }

    /**
     * Generate JSON Schema for instruction parameter.
     */
    private static function generateInstructionSchema(array $allInstructions): array
    {
        $schema = [
            'type' => 'array',
            'description' => '指令parameterarray，用于控制AI助理的行为。每个objectcontain name（指令name）和 value（指令value）field。单选type指令need从可选value中选择一个，开关type指令只能是 "on" 或 "off"。',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => '指令name，must与AI助理定义的指令name完全匹配',
                    ],
                    'value' => [
                        'type' => 'string',
                        'description' => '指令value，单选type从可选value中选择，开关type只能是 "on" 或 "off"',
                    ],
                ],
                'required' => ['name', 'value'],
                'additionalProperties' => false,
            ],
        ];

        // 如果有具体的指令info，generate更详细的 schema
        if (! empty($allInstructions)) {
            $examples = [];
            foreach ($allInstructions as $instructions) {
                foreach ($instructions as $group) {
                    if (empty($group['items'])) {
                        continue;
                    }

                    foreach ($group['items'] as $item) {
                        if (empty($item['name']) || ($item['hidden'] ?? false)) {
                            continue;
                        }

                        // Only process items with instruction_type = 1
                        if (($item['instruction_type'] ?? null) !== 1) {
                            continue;
                        }

                        if ($item['type'] === 1) {
                            // Single selection
                            $values = $item['values'] ?? [];
                            if (! empty($values)) {
                                $examples[] = [
                                    'name' => $item['name'],
                                    'value' => $values[0]['value'] ?? '',
                                ];
                            }
                        } elseif ($item['type'] === 2) {
                            // Switch
                            $defaultValue = $item['default_value'] ?? 'off';
                            $examples[] = [
                                'name' => $item['name'],
                                'value' => $defaultValue,
                            ];
                        }
                    }
                }
            }

            if (! empty($examples)) {
                $schema['examples'] = [array_slice($examples, 0, 3)]; // Show up to 3 examples
            }
        }

        return $schema;
    }

    /**
     * @return array<RegisteredTool>
     */
    private static function getTools(FlowDataIsolation $flowDataIsolation, array $toolIds): array
    {
        $permissionDataIsolation = PermissionDataIsolation::createByBaseDataIsolation($flowDataIsolation);
        $toolSetResources = di(OperationPermissionAppService::class)->getResourceOperationByUserIds(
            $permissionDataIsolation,
            ResourceType::ToolSet,
            [$flowDataIsolation->getCurrentUserId()]
        )[$flowDataIsolation->getCurrentUserId()] ?? [];
        $toolSetIds = array_keys($toolSetResources);

        $registeredTools = [];
        $toolFlows = ToolsExecutor::getToolFlows($flowDataIsolation, $toolIds);
        foreach ($toolFlows as $toolFlow) {
            if (! $toolFlow->hasCallback() && ! in_array($toolFlow->getToolSetId(), $toolSetIds)) {
                continue;
            }
            if (! $toolFlow->isEnabled()) {
                continue;
            }
            $toolFlowId = $toolFlow->getCode();
            if (isset($registeredTools[$toolFlow->getName()])) {
                continue;
            }

            $registeredTools[$toolFlow->getName()] = new RegisteredTool(
                tool: new Tool(
                    name: $toolFlow->getName(),
                    inputSchema: $toolFlow->getInput()?->getForm()?->getForm()?->toJsonSchema() ?? [],
                    description: $toolFlow->getDescription(),
                ),
                callable: function (array $arguments) use ($flowDataIsolation, $toolFlowId) {
                    $toolFlow = ToolsExecutor::getToolFlows($flowDataIsolation, [$toolFlowId])[0] ?? null;
                    if (! $toolFlow || ! $toolFlow->isEnabled()) {
                        $label = $toolFlow ? $toolFlow->getName() : $toolFlowId;
                        ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.disabled', ['label' => $label]);
                    }
                    $apiChatDTO = new DelightfulFlowApiChatDTO();
                    $apiChatDTO->setParams($arguments);
                    $apiChatDTO->setFlowCode($toolFlow->getCode());
                    $apiChatDTO->setFlowVersionCode($toolFlow->getVersionCode());
                    $apiChatDTO->setMessage('mcp_tool_call');
                    return di(DelightfulFlowExecuteAppService::class)->apiParamCallByRemoteTool($flowDataIsolation, $apiChatDTO, 'be_delightful_mcp_tool');
                },
            );
        }

        return array_values($registeredTools);
    }

    private static function getRedis(): RedisProxy
    {
        return di(RedisFactory::class)->get('default');
    }

    private static function buildRedisKey(string $mcpServerCode): string
    {
        return self::REDIS_KEY_PREFIX . $mcpServerCode;
    }
}
