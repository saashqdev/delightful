<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Application\Permission\Service\OperationPermissionAppService;
use App\Domain\Agent\Service\AgentDomainService;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Agent\Service\DelightfulAgentVersionDomainService;
use App\Domain\Agent\Service\DelightfulBotThirdPlatformChatDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Contact\Service\DelightfulAccountDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use App\Domain\Flow\Service\DelightfulFlowVersionDomainService;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Infrastructure\Core\Traits\DataIsolationTrait;
use App\Infrastructure\Util\Locker\RedisLocker;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;

abstract class AbstractAppService extends AbstractKernelAppService
{
    use DataIsolationTrait;

    protected LoggerInterface $logger;

    public function __construct(
        protected readonly DelightfulAgentDomainService $magicAgentDomainService,
        protected readonly DelightfulAgentVersionDomainService $magicAgentVersionDomainService,
        protected readonly DelightfulFlowDomainService $magicFlowDomainService,
        protected readonly DelightfulUserDomainService $magicUserDomainService,
        protected readonly DelightfulFlowVersionDomainService $magicFlowVersionDomainService,
        protected readonly FileDomainService $fileDomainService,
        protected readonly OperationPermissionAppService $operationPermissionAppService,
        protected readonly RedisLocker $redisLocker,
        protected readonly DelightfulAccountDomainService $magicAccountDomainService,
        protected readonly DelightfulBotThirdPlatformChatDomainService $magicBotThirdPlatformChatDomainService,
        protected readonly DelightfulDepartmentDomainService $magicDepartmentDomainService,
        protected readonly DelightfulDepartmentUserDomainService $magicDepartmentUserDomainService,
        protected readonly AgentDomainService $agentDomainService,
        protected readonly DelightfulConversationDomainService $magicConversationDomainService,
        public readonly LoggerFactory $loggerFactory,
        protected readonly Redis $redis,
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    protected function getAgentOperation(PermissionDataIsolation $permissionDataIsolation, int|string $agentId): Operation
    {
        if (empty($agentId)) {
            return Operation::None;
        }
        return $this->operationPermissionAppService->getOperationByResourceAndUser(
            $permissionDataIsolation,
            ResourceType::AgentCode,
            (string) $agentId,
            $permissionDataIsolation->getCurrentUserId()
        );
    }
}
