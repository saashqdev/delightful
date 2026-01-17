<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\Agent\Facade\Admin;

use App\Application\Flow\Service\DelightfulFlowAppService;
use App\Domain\Flow\Entity\DelightfulFlowToolSetEntity;
use App\Infrastructure\Util\ShadowCode\ShadowCode;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\Agent\Service\BeDelightfulAgentAiOptimizeAppService;
use Delightful\BeDelightful\Application\Agent\Service\BeDelightfulAgentAppService;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Query\BeDelightfulAgentQuery;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentOptimizationType;
use Delightful\BeDelightful\Interfaces\Agent\Assembler\BuiltinToolAssembler;
use Delightful\BeDelightful\Interfaces\Agent\Assembler\BeDelightfulAgentAssembler;
use Delightful\BeDelightful\Interfaces\Agent\DTO\BuiltinToolDTO;
use Delightful\BeDelightful\Interfaces\Agent\DTO\BeDelightfulAgentDTO;
use Delightful\BeDelightful\Interfaces\Agent\FormRequest\BeDelightfulAgentAiOptimizeFormRequest;
use Delightful\BeDelightful\Interfaces\Agent\FormRequest\BeDelightfulAgentOrderFormRequest;
use Delightful\BeDelightful\Interfaces\Agent\FormRequest\BeDelightfulAgentQueryFormRequest;
use Delightful\BeDelightful\Interfaces\Agent\FormRequest\BeDelightfulAgentSaveFormRequest;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class BeDelightfulAgentAdminApi extends AbstractBeDelightfulAdminApi
{
    #[Inject]
    protected BeDelightfulAgentAppService $beDelightfulAgentAppService;

    #[Inject]
    protected BeDelightfulAgentAiOptimizeAppService $beDelightfulAgentAiOptimizeAppService;

    #[Inject]
    protected DelightfulFlowAppService $delightfulFlowAppService;

    public function save(BeDelightfulAgentSaveFormRequest $request)
    {
        $authorization = $this->getAuthorization();

        $requestData = $request->validated();
        $DTO = new BeDelightfulAgentDTO($requestData);
        $promptShadow = $request->input('prompt_shadow');
        if ($promptShadow) {
            $promptShadow = json_decode(ShadowCode::unShadow($promptShadow), true);
            $DTO->setPrompt($promptShadow);
        }

        $DO = BeDelightfulAgentAssembler::createDO($DTO);

        $entity = $this->beDelightfulAgentAppService->save($authorization, $DO);
        $users = $this->beDelightfulAgentAppService->getUsers($entity->getOrganizationCode(), [$entity->getCreator(), $entity->getModifier()]);

        return BeDelightfulAgentAssembler::createDTO($entity, $users);
    }

    public function queries(BeDelightfulAgentQueryFormRequest $request)
    {
        $authorization = $this->getAuthorization();

        $requestData = $request->validated();
        $query = new BeDelightfulAgentQuery($requestData);
        $page = $this->createPage();

        $result = $this->beDelightfulAgentAppService->queries($authorization, $query, $page);

        return BeDelightfulAgentAssembler::createCategorizedListDTO(
            frequent: $result['frequent'],
            all: $result['all'],
            total: $result['total']
        );
    }

    public function show(string $code)
    {
        $authorization = $this->getAuthorization();
        $withToolSchema = (bool) $this->request->input('with_tool_schema', false);

        $entity = $this->beDelightfulAgentAppService->show($authorization, $code, $withToolSchema);

        $withPromptString = (bool) $this->request->input('with_prompt_string', false);

        $users = $this->beDelightfulAgentAppService->getUsers($entity->getOrganizationCode(), [$entity->getCreator(), $entity->getModifier()]);

        return BeDelightfulAgentAssembler::createDTO($entity, $users, $withPromptString);
    }

    public function destroy(string $code)
    {
        $authorization = $this->getAuthorization();
        $result = $this->beDelightfulAgentAppService->delete($authorization, $code);

        return ['success' => $result];
    }

    public function enable(string $code)
    {
        $authorization = $this->getAuthorization();
        $entity = $this->beDelightfulAgentAppService->enable($authorization, $code);

        $users = $this->beDelightfulAgentAppService->getUsers($entity->getOrganizationCode(), [$entity->getCreator(), $entity->getModifier()]);

        return BeDelightfulAgentAssembler::createDTO($entity, $users);
    }

    public function disable(string $code)
    {
        $authorization = $this->getAuthorization();
        $entity = $this->beDelightfulAgentAppService->disable($authorization, $code);

        $users = $this->beDelightfulAgentAppService->getUsers($entity->getOrganizationCode(), [$entity->getCreator(), $entity->getModifier()]);

        return BeDelightfulAgentAssembler::createDTO($entity, $users);
    }

    /**
     * Save agent order configuration.
     */
    public function saveOrder(BeDelightfulAgentOrderFormRequest $request)
    {
        $authorization = $this->getAuthorization();

        $requestData = $request->validated();
        $orderConfig = [
            'frequent' => $requestData['frequent'] ?? [],
            'all' => $requestData['all'],
        ];

        $this->beDelightfulAgentAppService->saveOrderConfig($authorization, $orderConfig);

        return ['message' => 'Agent order saved successfully'];
    }

    /**
     * Get built-in tools list.
     */
    public function tools()
    {
        return BuiltinToolAssembler::createToolCategoryListDTO();
    }

    /**
     * AI-optimize agent.
     */
    public function aiOptimize(BeDelightfulAgentAiOptimizeFormRequest $request)
    {
        $authorization = $this->getAuthorization();
        $requestData = $request->validated();

        // Create optimization type enum instance (FormRequest validation ensures validity)
        $optimizationType = BeDelightfulAgentOptimizationType::fromString($requestData['optimization_type']);

        // Use BeDelightfulAgentAssembler to create entity
        $DTO = new BeDelightfulAgentDTO($requestData['agent']);
        $promptShadow = $request->input('agent.prompt_shadow');
        if ($promptShadow) {
            $promptShadow = json_decode(ShadowCode::unShadow($promptShadow), true);
            $DTO->setPrompt($promptShadow);
        }
        $agentEntity = BeDelightfulAgentAssembler::createDO($DTO);

        // Only query tool information when optimizing content
        $availableTools = [];
        if ($optimizationType === BeDelightfulAgentOptimizationType::OptimizeContent) {
            // Tools available to the current user
            $builtinTools = BuiltinToolAssembler::createToolListDTO();
            $customToolSets = $this->delightfulFlowAppService->queryToolSets($authorization, false, false)['list'] ?? [];

            // Merge built-in tools and custom tools into unified format
            $availableTools = $this->mergeAvailableTools($builtinTools, $customToolSets);
        }

        // Call optimization service
        $optimizedEntity = $this->beDelightfulAgentAiOptimizeAppService->optimizeAgent(
            $authorization,
            $optimizationType,
            $agentEntity,
            $availableTools
        );

        return [
            'optimization_type' => $optimizationType->value,
            'agent' => BeDelightfulAgentAssembler::createDTO($optimizedEntity),
        ];
    }

    /**
     * Merge built-in tools and custom tools into unified format.
     * @param array<BuiltinToolDTO> $builtinTools
     * @param array<DelightfulFlowToolSetEntity> $customToolSets
     */
    private function mergeAvailableTools(array $builtinTools, array $customToolSets): array
    {
        $tools = [];

        // Process built-in tools
        foreach ($builtinTools as $tool) {
            $tools[$tool->getCode()] = [
                'code' => $tool->getCode(),
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'required' => $tool->isRequired(),
                'type' => 'builtin',
            ];
        }

        // Process custom tools
        foreach ($customToolSets as $customToolSet) {
            foreach ($customToolSet->getTools() as $tool) {
                $tools[$tool['code']] = [
                    'code' => $tool['code'],
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'required' => false,
                    'type' => 'custom',
                ];
            }
        }

        return $tools;
    }
}
