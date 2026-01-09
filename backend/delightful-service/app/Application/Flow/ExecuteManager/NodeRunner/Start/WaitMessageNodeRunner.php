<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Start;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Flow\Entity\DelightfulFlowWaitMessageEntity;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\WaitMessageNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Domain\Flow\Service\DelightfulFlowWaitMessageDomainService;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;

#[FlowNodeDefine(
    type: NodeType::WaitMessage->value,
    code: NodeType::WaitMessage->name,
    name: 'etc待',
    paramsConfig: WaitMessageNodeParamsConfig::class,
    version: 'v0',
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

        // ifisasforstartsectionpoint
        if ($executionData->getTriggerType() === TriggerType::WaitMessage) {
            $result = $this->chatMessage($vertexResult, $executionData);
            $vertexResult->setResult($result);
            $executionData->saveNodeContext($this->node->getNodeId(), $result);
            return;
        }

        // ifisasforrunlinesectionpoint onlyrecord,thenendwhenfrontexecute
        $waitMessageEntity = new DelightfulFlowWaitMessageEntity();
        $waitMessageEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $waitMessageEntity->setConversationId($executionData->getConversationId());
        $waitMessageEntity->setOriginConversationId($executionData->getOriginConversationId());
        $waitMessageEntity->setMessageId($executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId());
        $waitMessageEntity->setWaitNodeId($this->node->getNodeId());
        $waitMessageEntity->setFlowCode($executionData->getFlowCode());
        $waitMessageEntity->setFlowVersion($executionData->getFlowVersion());
        $waitMessageEntity->setCreator($executionData->getOperator()->getUid());
        // calculate timeout
        $params = $this->node->getParams();
        $timeoutConfig = $params['timeout_config'] ?? [];
        if ($timeoutConfig['enabled'] ?? false) {
            $intervalSeconds = $this->getIntervalSeconds($timeoutConfig['interval'] ?? 0, $timeoutConfig['unit'] ?? '');
            $waitMessageEntity->setTimeout(time() + $intervalSeconds);
        }

        // 暫o clockalsoisputtodatalibrarymiddle,back続considerputto objectstorage middle
        $persistenceData = $executionData->getPersistenceData();
        $waitMessageEntity->setPersistentData($persistenceData);

        $waitMessageDomainService->save(
            dataIsolation: $executionData->getDataIsolation(),
            savingWaitMessageEntity: $waitMessageEntity
        );

        $vertexResult->setChildrenIds([]);
    }
}
