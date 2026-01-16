<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Application\Agent\Service;

use DateTime;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentOptimizationType;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentTool;
use Hyperf\Odin\Api\Response\ChatCompletionResponse;
use Hyperf\Odin\Message\AssistantMessage;
use Qbhy\HyperfAuth\Authenticatable;

class BeDelightfulAgentAiOptimizeAppService extends AbstractBeDelightfulAppService
{
    public function optimizeAgent(Authenticatable $authorization, BeDelightfulAgentOptimizationType $optimizationType, BeDelightfulAgentEntity $agentEntity, array $availableTools): BeDelightfulAgentEntity
    {
        $dataIsolation = $this->createBeDelightfulDataIsolation($authorization);

        $agentEntity->setCreator($dataIsolation->getCurrentUserId());
        $agentEntity->setCreatedAt(new DateTime());
        $agentEntity->setModifier($dataIsolation->getCurrentUserId());
        $agentEntity->setUpdatedAt(new DateTime());
        $agentEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());

        if ($optimizationType->isNone()) {
            $this->logger->info('No optimization type selected, returning original entity.');
            return $agentEntity;
        }

        // 检查优化前提条件，不满足条件时直接返回原实体
        if ($this->checkOptimizationPreconditions($optimizationType, $agentEntity)) {
            $this->logger->info('Optimization preconditions not met, returning original entity.');
            return $agentEntity;
        }

        // 1. 获取优化 Agent（指定文件路径）
        $agentFilePath = SUPER_MAGIC_MODULE_PATH . '/src/Application/Agent/MicroAgent/AgentOptimizer.agent.yaml'; // @phpstan-ignore-line
        $optimizerAgent = $this->microAgentFactory->getAgent('BeDelightfulAgentOptimizer', $agentFilePath);

        // 2. 设置优化工具
        $optimizerAgent->setTools($this->getAgentOptimizerTools());

        // 3. 构建用户提示词
        $userPrompt = $this->buildUserPrompt($optimizationType, $agentEntity, $availableTools);

        // 4. 调用 AI 进行优化
        $response = $optimizerAgent->easyCall(
            organizationCode: $dataIsolation->getCurrentOrganizationCode(),
            userPrompt: $userPrompt,
            businessParams: [
                'organization_id' => $dataIsolation->getCurrentOrganizationCode(),
                'user_id' => $dataIsolation->getCurrentUserId(),
                'source_id' => 'super_magic_agent_optimizer',
            ]
        );

        // 5. 提取工具调用结果并更新实体
        return $this->extractToolCallResult($response, $agentEntity, $availableTools);
    }

    private function getAgentOptimizerTools(): array
    {
        return [
            // 1. 优化名称和描述工具
            [
                'type' => 'function',
                'function' => [
                    'name' => BeDelightfulAgentOptimizationType::OptimizeNameDescription->value,
                    'description' => '根据内容为智能体优化命名及描述',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => '智能体名称（必须是2-10个字符的简洁名称，如：小红书大师、文案专家）',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => '智能体描述（20-100个字符的功能描述）',
                            ],
                        ],
                        'required' => ['name', 'description'],
                    ],
                ],
            ],

            // 2. 优化内容工具
            [
                'type' => 'function',
                'function' => [
                    'name' => BeDelightfulAgentOptimizationType::OptimizeContent->value,
                    'description' => '根据名称和描述为智能体优化内容',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'prompt' => [
                                'type' => 'string',
                                'description' => '系统提示词内容',
                            ],
                            'tools' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                                'description' => '推荐的工具代码列表，只返回工具的code字段',
                            ],
                        ],
                        'required' => ['prompt'],
                    ],
                ],
            ],

            // 3. 优化名称工具
            [
                'type' => 'function',
                'function' => [
                    'name' => BeDelightfulAgentOptimizationType::OptimizeName->value,
                    'description' => '根据已填写的所有信息优化智能体名称',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => '优化后的智能体名称（必须是2-10个字符的简洁名称，不能是完整句子）',
                            ],
                        ],
                        'required' => ['name'],
                    ],
                ],
            ],

            // 4. 优化描述工具
            [
                'type' => 'function',
                'function' => [
                    'name' => BeDelightfulAgentOptimizationType::OptimizeDescription->value,
                    'description' => '根据已填写的所有信息优化智能体描述',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'description' => [
                                'type' => 'string',
                                'description' => '优化后的智能体描述',
                            ],
                        ],
                        'required' => ['description'],
                    ],
                ],
            ],
        ];
    }

    private function buildUserPrompt(BeDelightfulAgentOptimizationType $optimizationType, BeDelightfulAgentEntity $agentEntity, array $availableTools): string
    {
        $agentData = [
            'name' => $agentEntity->getName(),
            'description' => $agentEntity->getDescription(),
            'prompt' => $agentEntity->getPromptString(),
            'tools' => $agentEntity->getTools(),
        ];

        // 语言提示：若包含中文字符，则提示中文，否则自动
        $combined = (string) ($agentData['name'] . $agentData['description'] . $agentData['prompt']);
        $languageHint = preg_match('/\p{Han}/u', $combined) ? 'zh' : 'auto';

        $requestData = [
            'ot' => $optimizationType->value,
            'data' => $agentData,
            'rules' => [
                'tool' => 'single_call_match_type',
                'name' => '2-10_chars_no_punct_no_sentence',
                'desc' => '20-100_chars_value_focus',
                'content' => 'preserve_depth_format_supplement_sections',
                'ignore' => 'basic_tools_ignored',
                'diverse' => 'must_diff_prev',
                'no_copy' => 'forbidden_output_same_as_input',
                'lang' => 'match_input_and_headers',
            ],
            'meta' => [
                'ts' => time(),
                'lang_hint' => $languageHint,
                'src' => 'super_magic_agent_optimizer',
            ],
        ];

        // 如果是优化内容且有可用工具，添加到请求数据中
        if ($optimizationType === BeDelightfulAgentOptimizationType::OptimizeContent && ! empty($availableTools)) {
            $requestData['available_tools'] = array_values($availableTools);
        }

        $jsonString = json_encode($requestData, JSON_UNESCAPED_UNICODE);

        $instruction = '按 rules 进行一次优化，仅调用与 ot 对应的单一工具。输入(JSON)：';

        return $instruction . $jsonString;
    }

    private function extractToolCallResult(ChatCompletionResponse $response, BeDelightfulAgentEntity $agentEntity, array $availableTools): BeDelightfulAgentEntity
    {
        // 解析 response 中的工具调用
        // 如果没有工具调用或解析失败，返回原始实体

        $assistantMessage = $response->getFirstChoice()?->getMessage();
        if (! $assistantMessage instanceof AssistantMessage) {
            return $agentEntity;
        }
        if (! $assistantMessage->hasToolCalls()) {
            $this->logger->info('No assistant message selected, returning original entity.');
            return $agentEntity;
        }

        foreach ($assistantMessage->getToolCalls() as $toolCall) {
            $this->logger->info('tool_call', $toolCall->toArray());
            $toolName = $toolCall->getName();
            $arguments = $toolCall->getArguments();

            switch ($toolName) {
                case BeDelightfulAgentOptimizationType::OptimizeNameDescription->value:
                    if (isset($arguments['name'])) {
                        $agentEntity->setName($arguments['name']);
                    }
                    if (isset($arguments['description'])) {
                        $agentEntity->setDescription($arguments['description']);
                    }
                    break;
                case BeDelightfulAgentOptimizationType::OptimizeContent->value:
                    if (isset($arguments['prompt'])) {
                        // 处理转义字符，将 \\n、\\t、\\r 等转换为实际的换行符和制表符
                        $processedPrompt = stripcslashes($arguments['prompt']);

                        $promptData = [
                            'version' => '1.0.0',
                            'structure' => [
                                'string' => $processedPrompt,
                            ],
                        ];
                        $agentEntity->setPrompt($promptData);
                    }

                    // 处理工具推荐：只添加新工具，不修改或删除原有工具
                    if (isset($arguments['tools']) && is_array($arguments['tools'])) {
                        foreach ($arguments['tools'] as $toolCode) {
                            $tool = $this->createToolFromAvailableTools($toolCode, $availableTools);
                            if ($tool) {
                                $agentEntity->addTool($tool);
                            }
                        }
                    }
                    break;
                case BeDelightfulAgentOptimizationType::OptimizeName->value:
                    if (isset($arguments['name'])) {
                        $agentEntity->setName($arguments['name']);
                    }
                    break;
                case BeDelightfulAgentOptimizationType::OptimizeDescription->value:
                    if (isset($arguments['description'])) {
                        $agentEntity->setDescription($arguments['description']);
                    }
                    break;
            }
        }

        return $agentEntity;
    }

    /**
     * 检查优化前提条件.
     */
    private function checkOptimizationPreconditions(BeDelightfulAgentOptimizationType $optimizationType, BeDelightfulAgentEntity $agentEntity): bool
    {
        // 如果全部内容为空，则不进行优化
        if (empty($agentEntity->getName()) && empty($agentEntity->getDescription()) && empty($agentEntity->getPromptString())) {
            return true;
        }
        return false;
    }

    /**
     * 从可用工具列表中创建 BeDelightfulAgentTool 对象
     */
    private function createToolFromAvailableTools(string $toolCode, array $availableTools): ?BeDelightfulAgentTool
    {
        // 第一次查找：通过 code 字段匹配
        if (isset($availableTools[$toolCode])) {
            $toolInfo = $availableTools[$toolCode];
            return new BeDelightfulAgentTool([
                'code' => $toolInfo['code'],
                'name' => $toolInfo['name'] ?? '',
                'description' => $toolInfo['description'] ?? '',
                'type' => $toolInfo['type'] === 'builtin' ? 1 : 3,
            ]);
        }

        // 第二次查找：通过 name 字段匹配（容错机制）
        foreach ($availableTools as $tool) {
            if (($tool['name'] ?? '') === $toolCode) {
                return new BeDelightfulAgentTool([
                    'code' => $tool['code'],
                    'name' => $tool['name'] ?? '',
                    'description' => $tool['description'] ?? '',
                    'type' => $tool['type'] === 'builtin' ? 1 : 3,
                ]);
            }
        }

        // 如果两次查找都没有找到，返回 null
        return null;
    }
}
