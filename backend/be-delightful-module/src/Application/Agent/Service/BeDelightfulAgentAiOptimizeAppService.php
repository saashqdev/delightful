<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Agent\Service;

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

        // Check optimization preconditions, return original entity if conditions not met
        if ($this->checkOptimizationPreconditions($optimizationType, $agentEntity)) {
            $this->logger->info('Optimization preconditions not met, returning original entity.');
            return $agentEntity;
        }

        // 1. Get optimization Agent (specify file path)
        $agentFilePath = BE_DELIGHTFUL_MODULE_PATH . '/src/Application/Agent/MicroAgent/AgentOptimizer.agent.yaml'; // @phpstan-ignore-line
        $optimizerAgent = $this->microAgentFactory->getAgent('BeDelightfulAgentOptimizer', $agentFilePath);

        // 2. Set optimization tools
        $optimizerAgent->setTools($this->getAgentOptimizerTools());

        // 3. Build user prompt
        $userPrompt = $this->buildUserPrompt($optimizationType, $agentEntity, $availableTools);

        // 4. Call AI for optimization
        $response = $optimizerAgent->easyCall(
            organizationCode: $dataIsolation->getCurrentOrganizationCode(),
            userPrompt: $userPrompt,
            businessParams: [
                'organization_id' => $dataIsolation->getCurrentOrganizationCode(),
                'user_id' => $dataIsolation->getCurrentUserId(),
                'source_id' => 'super_magic_agent_optimizer',
            ]
        );

        // 5. Extract tool call result and update entity
        return $this->extractToolCallResult($response, $agentEntity, $availableTools);
    }

    private function getAgentOptimizerTools(): array
    {
        return [
            // 1. Optimize name and description tool
            [
                'type' => 'function',
                'function' => [
                    'name' => BeDelightfulAgentOptimizationType::OptimizeNameDescription->value,
                    'description' => 'Optimize agent name and description based on content',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'Agent name (must be a concise name of 2-10 characters, e.g., Social Media Master, Copywriting Expert)',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Agent description (20-100 characters functional description)',
                            ],
                        ],
                        'required' => ['name', 'description'],
                    ],
                ],
            ],

            // 2. Optimize content tool
            [
                'type' => 'function',
                'function' => [
                    'name' => BeDelightfulAgentOptimizationType::OptimizeContent->value,
                    'description' => 'Optimize agent content based on name and description',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'prompt' => [
                                'type' => 'string',
                                'description' => 'System prompt content',
                            ],
                            'tools' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                                'description' => 'List of recommended tool codes, only return the code field of tools',
                            ],
                        ],
                        'required' => ['prompt'],
                    ],
                ],
            ],

            // 3. Optimize name tool
            [
                'type' => 'function',
                'function' => [
                    'name' => BeDelightfulAgentOptimizationType::OptimizeName->value,
                    'description' => 'Optimize agent name based on all filled information',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'Optimized agent name (must be a concise name of 2-10 characters, cannot be a complete sentence)',
                            ],
                        ],
                        'required' => ['name'],
                    ],
                ],
            ],

            // 4. Optimize description tool
            [
                'type' => 'function',
                'function' => [
                    'name' => BeDelightfulAgentOptimizationType::OptimizeDescription->value,
                    'description' => 'Optimize agent description based on all filled information',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'description' => [
                                'type' => 'string',
                                'description' => 'Optimized agent description',
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

        // Language hint: if contains Chinese characters, set to Chinese, otherwise auto
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

        // If optimizing content and available tools exist, add to request data
        if ($optimizationType === BeDelightfulAgentOptimizationType::OptimizeContent && ! empty($availableTools)) {
            $requestData['available_tools'] = array_values($availableTools);
        }

        $jsonString = json_encode($requestData, JSON_UNESCAPED_UNICODE);

        $instruction = 'Perform one optimization according to rules, only call the single tool corresponding to ot. Input (JSON): ';

        return $instruction . $jsonString;
    }

    private function extractToolCallResult(ChatCompletionResponse $response, BeDelightfulAgentEntity $agentEntity, array $availableTools): BeDelightfulAgentEntity
    {
        // Parse tool calls in response
        // If no tool calls or parsing fails, return original entity

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
                        // Process escape characters, convert \\n, \\t, \\r etc. to actual newlines and tabs
                        $processedPrompt = stripcslashes($arguments['prompt']);

                        $promptData = [
                            'version' => '1.0.0',
                            'structure' => [
                                'string' => $processedPrompt,
                            ],
                        ];
                        $agentEntity->setPrompt($promptData);
                    }

                    // Handle tool recommendations: only add new tools, do not modify or delete existing tools
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
     * Check optimization preconditions.
     */
    private function checkOptimizationPreconditions(BeDelightfulAgentOptimizationType $optimizationType, BeDelightfulAgentEntity $agentEntity): bool
    {
        // If all content is empty, do not optimize
        if (empty($agentEntity->getName()) && empty($agentEntity->getDescription()) && empty($agentEntity->getPromptString())) {
            return true;
        }
        return false;
    }

    /**
     * Create BeDelightfulAgentTool object from available tools list.
     */
    private function createToolFromAvailableTools(string $toolCode, array $availableTools): ?BeDelightfulAgentTool
    {
        // First search: match by code field
        if (isset($availableTools[$toolCode])) {
            $toolInfo = $availableTools[$toolCode];
            return new BeDelightfulAgentTool([
                'code' => $toolInfo['code'],
                'name' => $toolInfo['name'] ?? '',
                'description' => $toolInfo['description'] ?? '',
                'type' => $toolInfo['type'] === 'builtin' ? 1 : 3,
            ]);
        }

        // Second search: match by name field (fault tolerance mechanism)
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

        // If both searches fail to find, return null
        return null;
    }
}
