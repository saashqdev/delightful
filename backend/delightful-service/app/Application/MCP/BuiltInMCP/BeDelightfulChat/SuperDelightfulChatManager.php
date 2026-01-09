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
        // 1. query所havecanuse agent
        $agents = di(DelightfulAgentDomainService::class)->getAgentByIds($agentIds);

        // ifnothavecanuse agents,directlyreturnemptyarray
        if (empty($agents)) {
            return [];
        }

        $hasAgents = false;
        $allInstructions = [];

        // 2. generateonesharebigmodelcalltoolcanreaddescription
        $description = <<<'MARKDOWN'
callMagic AI assistantconductconversation

canuse AI assistantlist:

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
                $agent->getAgentDescription() ?: '暂nodescription',
                $instructionDescription ? "\n  canusefinger令: {$instructionDescription}" : ''
            );

            // 收collection所havefinger令infouseatgenerate schema
            if ($instruction) {
                $allInstructions[$agent->getId()] = $instruction;
            }

            $hasAgents = true;
        }

        $usageInstructions = <<<'MARKDOWN'
useinstruction:
• mustprovide agent_id and message parameter
• conversation_id useatmaintainconversationcontinuousproperty,sameIDmessagewillsharedupdown文

MARKDOWN;

        $description .= $usageInstructions;

        // addfinger令parameterinstruction
        if (! empty($allInstructions)) {
            $instructionHelp = <<<'MARKDOWN'
finger令parameter instruction(optional):
• format:[{"name": "finger令name", "value": "finger令value"}, ...]
• single-selecttype:fromoptionalvaluemiddlechooseone,for example "yes", "no"
• switchtype:onlycanis "on" or "off"
• ifnotprovidefinger令parameter,willusedefaultvalue

callexample:
```json
{
  "agent_id": "123456",
  "message": "yougood,please helpIanalyzeonedown...",
  "conversation_id": "conv_001",
  "instruction": [
    {"name": "switch", "value": "on"},
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

        // generatefinger令 JSON Schema
        $instructionSchema = self::generateInstructionSchema($allInstructions);

        $registeredAgent = new RegisteredTool(
            tool: new Tool(
                name: 'call_delightful_agent',
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'agent_id' => [
                            'type' => 'string',
                            'description' => 'wantcall AI assistant ID',
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => 'sendgive AI assistantmessagecontent',
                        ],
                        'conversation_id' => [
                            'type' => 'string',
                            'description' => 'sessionID,useatmemoryfeature,samesessionIDmessagewill具havesharedupdown文',
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
                        $descriptions[] = "{$baseDescription}[single-select: " . implode(' | ', $values) . ']';
                    }
                } elseif ($item['type'] === 2) {
                    // Switch
                    $defaultValue = $item['default_value'] ?? 'off';
                    $descriptions[] = "{$baseDescription}[switch: on/off, default:{$defaultValue}]";
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
            'description' => 'finger令parameterarray,useatcontrolAIassistantlinefor.eachobjectcontain name(finger令name)and value(finger令value)field.single-selecttypefinger令needfromoptionalvaluemiddlechooseone,switchtypefinger令onlycanis "on" or "off".',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'finger令name,mustandAIassistantdefinitionfinger令name完allmatch',
                    ],
                    'value' => [
                        'type' => 'string',
                        'description' => 'finger令value,single-selecttypefromoptionalvaluemiddlechoose,switchtypeonlycanis "on" or "off"',
                    ],
                ],
                'required' => ['name', 'value'],
                'additionalProperties' => false,
            ],
        ];

        // ifhavespecificfinger令info,generatemoredetailed schema
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
