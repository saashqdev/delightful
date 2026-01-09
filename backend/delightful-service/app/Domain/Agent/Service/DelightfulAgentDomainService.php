<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Service;

use App\Domain\Agent\Constant\InstructType;
use App\Domain\Agent\Constant\DelightfulAgentVersionStatus;
use App\Domain\Agent\Constant\SystemInstructType;
use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Domain\Agent\Event\DelightfulAgentDeletedEvent;
use App\Domain\Agent\Event\DelightfulAgentSavedEvent;
use App\Domain\Agent\Factory\DelightfulAgentVersionFactory;
use App\Domain\Agent\Repository\Persistence\DelightfulAgentRepository;
use App\Domain\Agent\Repository\Persistence\DelightfulAgentVersionRepository;
use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\Context\RequestContext;
use App\Interfaces\Admin\DTO\Request\QueryPageAgentDTO;
use Delightful\AsyncEvent\AsyncEventUtil;
use Hyperf\DbConnection\Db;

/**
 * assistant service.
 */
class DelightfulAgentDomainService
{
    public function __construct(
        public DelightfulAgentRepository $agentRepository,
        public DelightfulAgentVersionRepository $agentVersionRepository,
        protected readonly CloudFileRepositoryInterface $cloudFileRepository
    ) {
    }

    /**
     * @return array{total: int, list: array<DelightfulAgentEntity>}
     */
    public function queries(DelightfulAgentQuery $query, Page $page): array
    {
        return $this->agentRepository->queries($query, $page);
    }

    public function getByFlowCode(string $flowCode): DelightfulAgentEntity
    {
        $agent = $this->agentRepository->getByFlowCode($flowCode);
        if ($agent === null) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'common.not_found', ['label' => $flowCode]);
        }
        return $agent;
    }

    /**
     * @return DelightfulAgentEntity[]
     */
    public function getByFlowCodes(array $flowCodes): array
    {
        return $this->agentRepository->getByFlowCodes($flowCodes);
    }

    public function saveAgent(DelightfulAgentEntity $agentEntity): DelightfulAgentEntity
    {
        $create = false;
        if (empty($agentEntity->getId())) {
            $agent = $this->agentRepository->insert($agentEntity);
            $create = true;

            // createassistanto clockaddsysteminteractionfingercommand
            $this->initSystemInstructs($agent->getOrganizationCode(), $agent->getId(), $agentEntity->getUpdatedUid());
        } else {
            // whethercanmodify
            $agent = $this->getAgentById($agentEntity->getId());
            $agent->setRobotName($agentEntity->getAgentName());
            $agent->setRobotDescription($agentEntity->getAgentDescription());
            $agent->setRobotAvatar($agentEntity->getAgentAvatar());

            $agent->setAgentName($agentEntity->getAgentName());
            $agent->setAgentDescription($agentEntity->getAgentDescription());
            $agent->setAgentAvatar($agentEntity->getAgentAvatar());

            $agent->setStartPage($agentEntity->getStartPage());
            $agent = $this->agentRepository->updateById($agent);
        }
        AsyncEventUtil::dispatch(new DelightfulAgentSavedEvent($agent, $create));
        return $agent;
    }

    public function deleteAgentById(string $id, string $organizationCode): bool
    {
        if (empty($id)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED);
        }
        Db::transaction(function () use ($id, $organizationCode) {
            $delightfulAgentEntity = $this->agentRepository->getAgentById($id);
            $this->agentRepository->deleteAgentById($id, $organizationCode);
            $this->agentVersionRepository->deleteByAgentId($id, $organizationCode);
            AsyncEventUtil::dispatch(new DelightfulAgentDeletedEvent($delightfulAgentEntity));
        });
        return true;
    }

    public function getAgentById(string $agentId): DelightfulAgentEntity
    {
        return $this->agentRepository->getAgentById($agentId);
    }

    public function updateDefaultVersion(string $agentId, string $versionId): void
    {
        $this->agentRepository->updateDefaultVersion($agentId, $versionId);
    }

    public function updateAgentStatus(string $agentId, int $status): void
    {
        if ($status !== DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value && $status !== DelightfulAgentVersionStatus::ENTERPRISE_DISABLED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_status_only_enable_or_disable');
        }

        $delightfulAgentEntity = new DelightfulAgentEntity();
        $delightfulAgentEntity->setId($agentId);
        $delightfulAgentEntity->setStatus($status);
        $this->agentRepository->updateStatus($agentId, $status);
    }

    /**
     * @return DelightfulAgentEntity[]
     */
    public function getEnabledAgents(): array
    {
        return $this->agentRepository->getEnabledAgents();
    }

    public function getById(string $agentId): DelightfulAgentEntity
    {
        return $this->agentRepository->getById($agentId);
    }

    public function getDefaultConversationAICodes(): array
    {
        $aiCodes = config('agent.default_conversation_ai_codes');
        if (! empty($aiCodes)) {
            return explode(',', $aiCodes);
        }
        return ['DELIGHTFUL-FLOW-676e4a53b17378-40076235'];
    }

    public function insertDefaultAssistantConversation(string $userId, string $aiCode): void
    {
        $this->agentRepository->insertDefaultAssistantConversation($userId, $aiCode);
    }

    public function isDefaultAssistantConversationExist(string $userId, string $aiCode): bool
    {
        return $this->agentRepository->isDefaultAssistantConversationExist($userId, $aiCode);
    }

    // quotient业codeitemfrontalsodependency
    public function getBotsByOrganization(RequestContext $requestContext, string $agentName, ?string $pageToken = null, int $pageSize = 50, ?string $descriptionKeyword = null): array
    {
        // getdataisolationobjectandgetcurrentorganizationorganizationcode
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();

        // getenableassistantlist
        $enabledAgents = $this->getEnabledAgents();

        // extractenableassistantlistmiddle agent_version_id
        $agentVersionIds = array_column($enabledAgents, 'agent_version_id');

        // getfingersetorganizationandassistantversionassistantdataanditstotal
        $page = ((int) ceil((int) $pageToken / $pageSize)) + 1;
        $agents = $this->agentVersionRepository->getAgentsByOrganization($organizationCode, $agentVersionIds, $page, $pageSize, $agentName, $descriptionKeyword);

        if (empty($agents)) {
            return [];
        }

        $agents = DelightfulAgentVersionFactory::toArrays($agents);

        // receivecollectionassistantavatarfilekey
        $fileKeys = array_column($agents, 'agent_avatar');
        // moveexceptemptyvalue
        $validFileKeys = array_filter($fileKeys, static fn ($fileKey) => ! empty($fileKey));

        // 按organizationminutegroupfileKeys
        $orgFileKeys = [];
        foreach ($validFileKeys as $fileKey) {
            $orgCode = explode('/', $fileKey, 2)[0] ?? '';
            if (! empty($orgCode)) {
                $orgFileKeys[$orgCode][] = $fileKey;
            }
        }

        // 按organizationbatchquantitygetlink
        $links = [];
        foreach ($orgFileKeys as $orgCode => $fileKeys) {
            $orgLinks = $this->cloudFileRepository->getLinks($orgCode, $fileKeys);
            $links[] = $orgLinks;
        }
        if (! empty($links)) {
            $links = array_merge(...$links);
        }

        // replaceeachassistantavatarlink
        foreach ($agents as &$agent) {
            $avatarKey = $agent['agent_avatar'];
            $fileLink = $links[$avatarKey] ?? null;
            $agent['agent_avatar'] = $fileLink?->getUrl() ?? '';
        }
        return $agents;
    }

    /**
     * @return array<DelightfulAgentEntity>
     */
    public function getAgentByIds(array $agentIds): array
    {
        return $this->agentRepository->getAgentByIds($agentIds);
    }

    /**
     * saveassistantinteractionfingercommand.
     */
    public function updateInstruct(string $organizationCode, string $agentId, array $instructs, string $userId = '', bool $valid = true): array
    {
        if ($valid) {
            // validationnormalinteractionfingercommand
            InstructType::validateInstructs($instructs);

            // ensuresysteminteractionfingertoken storagein,ifmissingthensupplement
            $instructs = SystemInstructType::ensureSystemInstructs($instructs);
        }
        // save
        $this->agentRepository->updateInstruct($organizationCode, $agentId, $instructs, $userId);
        return $instructs;
    }

    public function associateFlowWithAgent(string $agentId, string $flowCode): void
    {
        $this->agentRepository->updateFlowCode($agentId, $flowCode);
    }

    /**
     * queryenterprisedown haveassistant,itemitemquery:status,createperson,search.
     * @return array<DelightfulAgentEntity>
     */
    public function queriesAgents(string $organizationCode, QueryPageAgentDTO $queryPageAgentDTO): array
    {
        return $this->agentRepository->queriesAgents($organizationCode, $queryPageAgentDTO);
    }

    public function queriesAgentsCount(string $organizationCode, QueryPageAgentDTO $queryPageAgentDTO): int
    {
        return $this->agentRepository->queriesAgentsCount($organizationCode, $queryPageAgentDTO);
    }

    /**
     * getenterprisedown haveassistantcreateperson.
     * @return array<string>
     */
    public function getOrganizationAgentsCreators(string $organizationCode): array
    {
        return $this->agentRepository->getOrganizationAgentsCreators($organizationCode);
    }

    /**
     * initializesysteminteractionfingercommand.
     */
    private function initSystemInstructs(string $organizationCode, string $agentId, string $userId): void
    {
        $systemInstructs = SystemInstructType::getDefaultInstructs();
        $this->agentRepository->updateInstruct($organizationCode, $agentId, $systemInstructs, $userId);
    }
}
