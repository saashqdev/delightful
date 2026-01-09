<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Start\V1;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Flow\Entity\DelightfulFlowWaitMessageEntity;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\V1\WaitMessageNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Domain\Flow\Service\DelightfulFlowWaitMessageDomainService;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;

#[FlowNodeDefine(
    type: NodeType::WaitMessage->value,
    code: NodeType::WaitMessage->name,
    name: 'etc待',
    paramsConfig: WaitMessageNodeParamsConfig::class,
    version: 'v1',
    singleDebug: false,
    needInput: false,
    needOutput: true
)]
class WaitMessageNodeRunner extends AbstractStartNodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        $dataIsolation = $executionData->getDataIsolation();
        $waitMessageDomainService = di(DelightfulFlowWaitMessageDomainService::class);

        // if是作为startsectionpoint
        if ($executionData->getTriggerType() === TriggerType::WaitMessage) {
            $result = $this->chatMessage($vertexResult, $executionData);
            $vertexResult->setResult($result);
            $executionData->saveNodeContext($this->node->getNodeId(), $result);
            return;
        }

        // if是作为运linesectionpoint 仅record，thenendwhenfrontexecute
        $waitMessageEntity = new DelightfulFlowWaitMessageEntity();
        $waitMessageEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $waitMessageEntity->setConversationId($executionData->getConversationId());
        $waitMessageEntity->setOriginConversationId($executionData->getOriginConversationId());
        $waitMessageEntity->setMessageId($executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId());
        $waitMessageEntity->setWaitNodeId($this->node->getNodeId());
        $waitMessageEntity->setFlowCode($executionData->getFlowCode());
        $waitMessageEntity->setFlowVersion($executionData->getFlowVersion());
        $waitMessageEntity->setCreator($executionData->getOperator()->getUid());

        /** @var WaitMessageNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();
        $timeoutConfig = $paramsConfig->getTimeoutConfig();
        if ($timeoutConfig->isEnabled()) {
            $intervalSeconds = $this->getIntervalSeconds($timeoutConfig->getInterval(), $timeoutConfig->getUnit());
            $waitMessageEntity->setTimeout(time() + $intervalSeconds);
        }

        // 暂o clockalso是放todatalibrarymiddle，back续考虑放to objectstorage middle
        $persistenceData = $executionData->getPersistenceData();
        $waitMessageEntity->setPersistentData($persistenceData);

        $waitMessageDomainService->save(
            dataIsolation: $executionData->getDataIsolation(),
            savingWaitMessageEntity: $waitMessageEntity
        );

        $vertexResult->setChildrenIds([]);
    }
}
