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
        protected readonly DelightfulAgentDomainService $delightfulAgentDomainService,
        protected readonly FileDomainService $fileDomainService,
        protected readonly DelightfulConversationDomainService $delightfulConversationDomainService,
    ) {
    }

    public function square(): array
    {
        // 返回 agent 列表information
        return $this->userDomainService->getAgentList();
    }

    public function getAgentUserId(string $agentId = ''): string
    {
        $flow = $this->delightfulAgentDomainService->getAgentById($agentId);
        if (empty($flow->getFlowCode())) {
            ExceptionBuilder::throw(AgentErrorCode::AGENT_NOT_FOUND, 'flow_code not found');
        }
        $flowCode = $flow->getFlowCode();

        $dataIsolation = DataIsolation::create();
        $dataIsolation->setCurrentOrganizationCode($flow->getOrganizationCode());
        // 根据flowCode 查询user_id
        $delightfulUserEntity = $this->userDomainService->getByAiCode($dataIsolation, $flowCode);
        if (empty($delightfulUserEntity->getUserId())) {
            ExceptionBuilder::throw(AgentErrorCode::AGENT_NOT_FOUND, 'agent_user_id not found');
        }
        return $delightfulUserEntity->getUserId();
    }

    /**
     * @param DelightfulUserAuthorization $authenticatable
     * @return DelightfulAgentEntity[]
     */
    public function getAgentsForAdmin(array $agentIds, Authenticatable $authenticatable): array
    {
        // 获取机器人information
        $delightfulAgentEntities = $this->delightfulAgentDomainService->getAgentByIds($agentIds);

        $filePaths = array_column($delightfulAgentEntities, 'agent_avatar');
        $fileLinks = $this->fileDomainService->getLinks($authenticatable->getOrganizationCode(), $filePaths);

        foreach ($delightfulAgentEntities as $delightfulAgentEntity) {
            $fileLink = $fileLinks[$delightfulAgentEntity->getAgentAvatar()] ?? null;
            $delightfulAgentEntity->setAgentAvatar($fileLink?->getUrl() ?? '');
        }
        return $delightfulAgentEntities;
    }
}
