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
        protected readonly DelightfulFlowAIModelDomainService $delightfulFlowAIModelDomainService,
        protected readonly DelightfulFlowDomainService $delightfulFlowDomainService,
        protected readonly FileDomainService $fileDomainService,
        protected readonly DelightfulUserDomainService $delightfulUserDomainService,
        protected readonly OperationPermissionAppService $operationPermissionAppService,
        protected readonly DelightfulFlowToolSetDomainService $delightfulFlowToolSetDomainService,
        protected readonly DelightfulFlowDraftDomainService $delightfulFlowDraftDomainService,
        protected readonly DelightfulFlowApiKeyDomainService $delightfulFlowApiKeyDomainService,
        protected readonly DelightfulAgentDomainService $delightfulAgentDomainService,
        protected readonly DelightfulAgentVersionDomainService $delightfulAgentVersionDomainService,
        protected readonly DelightfulFlowPermissionDomainService $delightfulFlowPermissionDomainService,
        protected readonly DelightfulConversationDomainService $delightfulConversationDomainService,
        protected readonly DelightfulChatFileDomainService $delightfulChatFileDomainService,
        protected readonly KnowledgeBaseDomainService $delightfulFlowKnowledgeDomainService,
        protected readonly DelightfulFlowTriggerTestcaseDomainService $delightfulFlowTriggerTestcaseDomainService,
        protected readonly DelightfulFlowVersionDomainService $delightfulFlowVersionDomainService,
        protected readonly DelightfulFlowWaitMessageDomainService $delightfulFlowWaitMessageDomainService,
        protected readonly DelightfulOrganizationEnvDomainService $delightfulEnvironmentDomainService,
        protected readonly DelightfulFlowExecuteLogDomainService $delightfulFlowExecuteLogDomainService,
        protected readonly DelightfulAccountDomainService $delightfulAccountDomainService,
        protected readonly AdminProviderDomainService $serviceProviderDomainService,
        protected readonly KnowledgeBaseDocumentDomainService $delightfulFlowDocumentDomainService,
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
        $delightfulFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $flowCode);
        if (! $delightfulFlow) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $flowCode]);
        }
        $operation = $this->getFlowOperation($dataIsolation, $delightfulFlow);
        $operation->validate($checkOperation, $delightfulFlow->getCode());
        $delightfulFlow->setUserOperation($operation->value);

        return $delightfulFlow;
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
            $agentId = $this->delightfulAgentDomainService->getByFlowCode($flowEntity->getCode())->getId();
        }
        return $this->operationPermissionAppService->getOperationByResourceAndUser(
            $permissionDataIsolation,
            ResourceType::AgentCode,
            $agentId,
            $dataIsolation->getCurrentUserId()
        );
    }
}
