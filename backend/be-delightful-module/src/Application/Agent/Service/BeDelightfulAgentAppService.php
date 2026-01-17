<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Agent\Service;

use App\Application\Contact\UserSetting\UserSettingKey;
use App\Application\Flow\ExecuteManager\NodeRunner\LLM\ToolsExecutor;
use App\Application\Flow\Service\DelightfulFlowExecuteAppService;
use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\Contact\Service\DelightfulUserSettingDomainService;
use App\Domain\Mode\Entity\ModeEntity;
use App\Domain\Mode\Entity\ValueQuery\ModeQuery;
use App\Domain\Mode\Service\ModeDomainService;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\DTO\DelightfulFlowApiChatDTO;
use DateTime;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Query\BeDelightfulAgentQuery;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentDataIsolation;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentType;
use Delightful\BeDelightful\ErrorCode\BeDelightfulErrorCode;
use Hyperf\Di\Annotation\Inject;
use Qbhy\HyperfAuth\Authenticatable;

class BeDelightfulAgentAppService extends AbstractBeDelightfulAppService
{
    #[Inject]
    protected DelightfulUserSettingDomainService $delightfulUserSettingDomainService;

    #[Inject]
    protected ModeDomainService $modeDomainService;

    public function show(Authenticatable $authorization, string $code, bool $withToolSchema = false): BeDelightfulAgentEntity
    {
        $dataIsolation = $this->createBeDelightfulDataIsolation($authorization);
        $flowDataIsolation = $this->createFlowDataIsolation($authorization);

        $agent = $this->beDelightfulAgentDomainService->getByCodeWithException($dataIsolation, $code);
        if ($withToolSchema) {
            $remoteToolCodes = [];
            foreach ($agent->getTools() as $tool) {
                if ($tool->getType()->isRemote()) {
                    $remoteToolCodes[] = $tool->getCode();
                }
            }
            // Get tool definitions
            $remoteTools = ToolsExecutor::getToolFlows($flowDataIsolation, $remoteToolCodes, true);
            foreach ($agent->getTools() as $tool) {
                $remoteTool = $remoteTools[$tool->getCode()] ?? null;
                if ($remoteTool) {
                    $tool->setSchema($remoteTool->getInput()->getForm()?->getForm()->toJsonSchema());
                }
            }
        }
        return $agent;
    }

    /**
     * @return array{frequent: array<BeDelightfulAgentEntity>, all: array<BeDelightfulAgentEntity>, total: int}
     */
    public function queries(Authenticatable $authorization, BeDelightfulAgentQuery $query, Page $page): array
    {
        $dataIsolation = $this->createBeDelightfulDataIsolation($authorization);

        // Currently can only query own agents, full query
        $query->setCreatorId($authorization->getId());
        $page->disable();
        $query->setSelect(['id', 'code', 'name', 'description', 'icon', 'icon_type']); // Only select necessary fields for list

        $result = $this->beDelightfulAgentDomainService->queries($dataIsolation, $query, $page);

        // Merge builtin models
        $builtinAgents = $this->getBuiltinAgent($dataIsolation);
        if (! $page->isEnabled()) {
            $result['list'] = array_merge($builtinAgents, $result['list']);
            $result['total'] += count($builtinAgents);
        }

        // Categorize results based on user arrangement configuration
        $orderConfig = $this->getOrderConfig($authorization);

        return $this->categorizeAgents($result['list'], $result['total'], $orderConfig);
    }

    public function save(Authenticatable $authorization, BeDelightfulAgentEntity $entity): BeDelightfulAgentEntity
    {
        $dataIsolation = $this->createBeDelightfulDataIsolation($authorization);

        return $this->beDelightfulAgentDomainService->save($dataIsolation, $entity);
    }

    public function delete(Authenticatable $authorization, string $code): bool
    {
        $dataIsolation = $this->createBeDelightfulDataIsolation($authorization);

        return $this->beDelightfulAgentDomainService->delete($dataIsolation, $code);
    }

    public function enable(Authenticatable $authorization, string $code): BeDelightfulAgentEntity
    {
        $dataIsolation = $this->createBeDelightfulDataIsolation($authorization);

        return $this->beDelightfulAgentDomainService->enable($dataIsolation, $code);
    }

    public function disable(Authenticatable $authorization, string $code): BeDelightfulAgentEntity
    {
        $dataIsolation = $this->createBeDelightfulDataIsolation($authorization);

        return $this->beDelightfulAgentDomainService->disable($dataIsolation, $code);
    }

    /**
     * Save agent arrangement configuration.
     * @param array{frequent: array<string>, all: array<string>} $orderConfig
     */
    public function saveOrderConfig(Authenticatable $authorization, array $orderConfig): DelightfulUserSettingEntity
    {
        $dataIsolation = $this->createContactDataIsolation($authorization);
        $entity = new DelightfulUserSettingEntity();
        $entity->setKey(UserSettingKey::BeDelightfulAgentSort->value);
        $entity->setValue($orderConfig);

        return $this->delightfulUserSettingDomainService->save($dataIsolation, $entity);
    }

    /**
     * Get agent arrangement configuration.
     * @return null|array{frequent: array<string>, all: array<string>}
     */
    public function getOrderConfig(Authenticatable $authorization): ?array
    {
        $dataIsolation = $this->createContactDataIsolation($authorization);
        $setting = $this->delightfulUserSettingDomainService->get($dataIsolation, UserSettingKey::BeDelightfulAgentSort->value);

        return $setting?->getValue();
    }

    public function executeTool(Authenticatable $authorization, array $params): array
    {
        $toolCode = $params['code'] ?? '';
        $arguments = $params['arguments'] ?? [];
        if (empty($toolCode)) {
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.empty', ['label' => 'code']);
        }

        $flowDataIsolation = $this->createFlowDataIsolation($authorization);

        $toolFlow = ToolsExecutor::getToolFlows($flowDataIsolation, [$toolCode])[0] ?? null;
        if (! $toolFlow || ! $toolFlow->isEnabled()) {
            $label = $toolFlow ? $toolFlow->getName() : $toolCode;
            ExceptionBuilder::throw(BeDelightfulErrorCode::ValidateFailed, 'common.disabled', ['label' => $label]);
        }
        $apiChatDTO = new DelightfulFlowApiChatDTO();
        $apiChatDTO->setParams($arguments);
        $apiChatDTO->setFlowCode($toolFlow->getCode());
        $apiChatDTO->setFlowVersionCode($toolFlow->getVersionCode());
        $apiChatDTO->setMessage('be_delightful_tool_call');
        return di(DelightfulFlowExecuteAppService::class)->apiParamCallByRemoteTool(
            $flowDataIsolation,
            $apiChatDTO,
            'be_delightful_tool_call'
        );
    }

    /**
     * Categorize agent list into frequent and all according to user configuration.
     */
    private function categorizeAgents(array $agents, int $total, ?array $orderConfig): array
    {
        // If no user configuration, use default configuration: first 6 builtin agents as frequent
        if (empty($orderConfig)) {
            $orderConfig = $this->getDefaultOrderConfig($agents);
        }

        $frequentCodes = $orderConfig['frequent'] ?? [];
        $allOrder = $orderConfig['all'] ?? [];

        // Create code to entity mapping
        $agentMap = [];
        foreach ($agents as $agent) {
            $agentMap[$agent->getCode()] = $agent;
        }

        // Build frequent list
        $frequent = [];
        foreach ($frequentCodes as $code) {
            if (isset($agentMap[$code])) {
                $agentMap[$code]->setCategory('frequent');
                $frequent[] = $agentMap[$code];
            }
        }

        // Build all list (excluding those in frequent)
        $all = [];
        $frequentCodesSet = array_flip($frequentCodes);

        // If sort configuration exists, sort by configuration
        if (! empty($allOrder)) {
            foreach ($allOrder as $code) {
                if (isset($agentMap[$code]) && ! isset($frequentCodesSet[$code])) {
                    $agentMap[$code]->setCategory('all');
                    $all[] = $agentMap[$code];
                }
            }

            // Add agents not in sort configuration
            foreach ($agents as $agent) {
                $code = $agent->getCode();
                if (! in_array($code, $allOrder) && ! isset($frequentCodesSet[$code])) {
                    $agent->setCategory('all');
                    $all[] = $agent;
                }
            }
        } else {
            // No sort configuration, directly filter frequent
            foreach ($agents as $agent) {
                if (! isset($frequentCodesSet[$agent->getCode()])) {
                    $agent->setCategory('all');
                    $all[] = $agent;
                }
            }
        }

        return [
            'frequent' => $frequent,
            'all' => $all,
            'total' => $total,
        ];
    }

    /**
     * Get default sort configuration: first 6 builtin agents as frequent.
     * @param array<BeDelightfulAgentEntity> $agents
     */
    private function getDefaultOrderConfig(array $agents): array
    {
        $builtinCodes = [];
        $customCodes = [];

        foreach ($agents as $agent) {
            if ($agent->getType()->isBuiltIn()) {
                $builtinCodes[] = $agent->getCode();
            } else {
                $customCodes[] = $agent->getCode();
            }
        }

        // First 6 builtin agents as frequent
        $frequent = array_slice($builtinCodes, 0, 6);

        // all includes all agents (builtin + custom)
        $all = array_merge($builtinCodes, $customCodes);

        return [
            'frequent' => $frequent,
            'all' => $all,
        ];
    }

    /**
     * @return array<BeDelightfulAgentEntity>
     */
    private function getBuiltinAgent(BeDelightfulAgentDataIsolation $beDelightfulAgentDataIsolation): array
    {
        $modeDataIsolation = $this->createModeDataIsolation($beDelightfulAgentDataIsolation);
        $modeDataIsolation->setOnlyOfficialOrganization(true);
        $query = new ModeQuery(excludeDefault: true, status: true);
        $modesResult = $this->modeDomainService->getModes($modeDataIsolation, $query, Page::createNoPage());
        $list = [];
        foreach ($modesResult['list'] as $mode) {
            $list[] = $this->createBuiltinAgentEntityByMode($beDelightfulAgentDataIsolation, $mode);
        }
        return $list;
    }

    private function createBuiltinAgentEntityByMode(BeDelightfulAgentDataIsolation $beDelightfulAgentDataIsolation, ModeEntity $modeEntity): BeDelightfulAgentEntity
    {
        $entity = new BeDelightfulAgentEntity();

        // Set basic information
        $entity->setOrganizationCode($beDelightfulAgentDataIsolation->getCurrentOrganizationCode());
        $entity->setCode($modeEntity->getIdentifier());
        $entity->setName($modeEntity->getName());
        $entity->setDescription($modeEntity->getPlaceholder());
        $entity->setIcon([
            'url' => $modeEntity->getIconUrl(),
            'type' => $modeEntity->getIcon(),
            'color' => $modeEntity->getColor(),
        ]);
        $entity->setIconType($modeEntity->getIconType());
        $entity->setType(BeDelightfulAgentType::Built_In);
        $entity->setEnabled(true);
        $entity->setPrompt([]);
        $entity->setTools([]);

        // Set system creation information
        $entity->setCreator('system');
        $entity->setCreatedAt(new DateTime());
        $entity->setModifier('system');
        $entity->setUpdatedAt(new DateTime());

        return $entity;
    }
}
