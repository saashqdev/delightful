<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Application\KnowledgeBase\Service\Strategy\KnowledgeBase\KnowledgeBaseStrategyInterface;
use App\Application\Permission\Service\OperationPermissionAppService;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Agent\Service\DelightfulAgentVersionDomainService;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation as ContactDataIsolation;
use App\Domain\Contact\Service\DelightfulAccountDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Domain\Flow\Service\DelightfulFlowAIModelDomainService;
use App\Domain\Flow\Service\DelightfulFlowApiKeyDomainService;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use App\Domain\Flow\Service\DelightfulFlowDraftDomainService;
use App\Domain\Flow\Service\DelightfulFlowExecuteLogDomainService;
use App\Domain\Flow\Service\DelightfulFlowPermissionDomainService;
use App\Domain\Flow\Service\DelightfulFlowToolSetDomainService;
use App\Domain\Flow\Service\DelightfulFlowTriggerTestcaseDomainService;
use App\Domain\Flow\Service\DelightfulFlowVersionDomainService;
use App\Domain\Flow\Service\DelightfulFlowWaitMessageDomainService;
use App\Domain\KnowledgeBase\Service\KnowledgeBaseDocumentDomainService;
use App\Domain\KnowledgeBase\Service\KnowledgeBaseDomainService;
use App\Domain\OrganizationEnvironment\Service\DelightfulOrganizationEnvDomainService;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Domain\Provider\Service\AdminProviderDomainService;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

abstract class AbstractFlowAppService extends AbstractKernelAppService
{
    public function __construct(
        protected readonly DelightfulFlowAIModelDomainService $magicFlowAIModelDomainService,
        protected readonly DelightfulFlowDomainService $magicFlowDomainService,
        protected readonly FileDomainService $fileDomainService,
        protected readonly DelightfulUserDomainService $magicUserDomainService,
        protected readonly OperationPermissionAppService $operationPermissionAppService,
        protected readonly DelightfulFlowToolSetDomainService $magicFlowToolSetDomainService,
        protected readonly DelightfulFlowDraftDomainService $magicFlowDraftDomainService,
        protected readonly DelightfulFlowApiKeyDomainService $magicFlowApiKeyDomainService,
        protected readonly DelightfulAgentDomainService $magicAgentDomainService,
        protected readonly DelightfulAgentVersionDomainService $magicAgentVersionDomainService,
        protected readonly DelightfulFlowPermissionDomainService $magicFlowPermissionDomainService,
        protected readonly DelightfulConversationDomainService $magicConversationDomainService,
        protected readonly DelightfulChatFileDomainService $magicChatFileDomainService,
        protected readonly KnowledgeBaseDomainService $magicFlowKnowledgeDomainService,
        protected readonly DelightfulFlowTriggerTestcaseDomainService $magicFlowTriggerTestcaseDomainService,
        protected readonly DelightfulFlowVersionDomainService $magicFlowVersionDomainService,
        protected readonly DelightfulFlowWaitMessageDomainService $magicFlowWaitMessageDomainService,
        protected readonly DelightfulOrganizationEnvDomainService $magicEnvironmentDomainService,
        protected readonly DelightfulFlowExecuteLogDomainService $magicFlowExecuteLogDomainService,
        protected readonly DelightfulAccountDomainService $magicAccountDomainService,
        protected readonly AdminProviderDomainService $serviceProviderDomainService,
        protected readonly KnowledgeBaseDocumentDomainService $magicFlowDocumentDomainService,
        protected readonly KnowledgeBaseStrategyInterface $knowledgeBaseStrategy,
    ) {
    }

    protected function createContactDataIsolation(FlowDataIsolation $dataIsolation): ContactDataIsolation
    {
        return ContactDataIsolation::create($dataIsolation->getCurrentOrganizationCode(), $dataIsolation->getCurrentUserId());
    }

    protected function getFlowAndValidateOperation(FlowDataIsolation $dataIsolation, string $flowCode, string $checkOperation): DelightfulFlowEntity
    {
        if (empty($flowCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow_code']);
        }
        $magicFlow = $this->magicFlowDomainService->getByCode($dataIsolation, $flowCode);
        if (! $magicFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $flowCode]);
        }
        $operation = $this->getFlowOperation($dataIsolation, $magicFlow);
        $operation->validate($checkOperation, $magicFlow->getCode());
        $magicFlow->setUserOperation($operation->value);

        return $magicFlow;
    }

    protected function getFlowOperation(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $flowEntity): Operation
    {
        $permissionDataIsolation = $this->createPermissionDataIsolation($dataIsolation);

        return match ($flowEntity->getType()) {
            Type::Main => $this->getOperationByMain($permissionDataIsolation, $flowEntity),
            Type::Sub => $this->operationPermissionAppService->getOperationByResourceAndUser(
                $permissionDataIsolation,
                ResourceType::SubFlowCode,
                $flowEntity->getCode(),
                $dataIsolation->getCurrentUserId()
            ),
            Type::Tools => $this->operationPermissionAppService->getOperationByResourceAndUser(
                $permissionDataIsolation,
                ResourceType::ToolSet,
                $flowEntity->getToolSetId(),
                $dataIsolation->getCurrentUserId()
            ),
            default => Operation::None,
        };
    }

    protected function getKnowledgeOperation(FlowDataIsolation $dataIsolation, int|string $knowledgeCode): Operation
    {
        $permissionDataIsolation = $this->createPermissionDataIsolation($dataIsolation);

        if (empty($knowledgeCode)) {
            return Operation::None;
        }
        return $this->operationPermissionAppService->getOperationByResourceAndUser(
            $permissionDataIsolation,
            ResourceType::Knowledge,
            (string) $knowledgeCode,
            $permissionDataIsolation->getCurrentUserId()
        );
    }

    private function getOperationByMain(PermissionDataIsolation $dataIsolation, DelightfulFlowEntity $flowEntity): Operation
    {
        $permissionDataIsolation = $this->createPermissionDataIsolation($dataIsolation);
        if (! $agentId = $flowEntity->getAgentId()) {
            $agentId = $this->magicAgentDomainService->getByFlowCode($flowEntity->getCode())->getId();
        }
        return $this->operationPermissionAppService->getOperationByResourceAndUser(
            $permissionDataIsolation,
            ResourceType::AgentCode,
            $agentId,
            $dataIsolation->getCurrentUserId()
        );
    }
}
