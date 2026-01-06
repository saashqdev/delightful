<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Qbhy\HyperfAuth\Authenticatable;

class DelightfulAgentAppService extends AbstractAppService
{
    public function __construct(
        protected readonly DelightfulUserDomainService $userDomainService,
        protected readonly DelightfulAgentDomainService $magicAgentDomainService,
        protected readonly FileDomainService $fileDomainService,
        protected readonly DelightfulConversationDomainService $magicConversationDomainService,
    ) {
    }

    public function square(): array
    {
        // 返回 agent 列表信息
        return $this->userDomainService->getAgentList();
    }

    public function getAgentUserId(string $agentId = ''): string
    {
        $flow = $this->magicAgentDomainService->getAgentById($agentId);
        if (empty($flow->getFlowCode())) {
            ExceptionBuilder::throw(AgentErrorCode::AGENT_NOT_FOUND, 'flow_code not found');
        }
        $flowCode = $flow->getFlowCode();

        $dataIsolation = DataIsolation::create();
        $dataIsolation->setCurrentOrganizationCode($flow->getOrganizationCode());
        // 根据flowCode 查询user_id
        $magicUserEntity = $this->userDomainService->getByAiCode($dataIsolation, $flowCode);
        if (empty($magicUserEntity->getUserId())) {
            ExceptionBuilder::throw(AgentErrorCode::AGENT_NOT_FOUND, 'agent_user_id not found');
        }
        return $magicUserEntity->getUserId();
    }

    /**
     * @param DelightfulUserAuthorization $authenticatable
     * @return DelightfulAgentEntity[]
     */
    public function getAgentsForAdmin(array $agentIds, Authenticatable $authenticatable): array
    {
        // 获取机器人信息
        $magicAgentEntities = $this->magicAgentDomainService->getAgentByIds($agentIds);

        $filePaths = array_column($magicAgentEntities, 'agent_avatar');
        $fileLinks = $this->fileDomainService->getLinks($authenticatable->getOrganizationCode(), $filePaths);

        foreach ($magicAgentEntities as $magicAgentEntity) {
            $fileLink = $fileLinks[$magicAgentEntity->getAgentAvatar()] ?? null;
            $magicAgentEntity->setAgentAvatar($fileLink?->getUrl() ?? '');
        }
        return $magicAgentEntities;
    }
}
