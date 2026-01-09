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
    name: '等待',
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

        // if是作为开始节点
        if ($executionData->getTriggerType() === TriggerType::WaitMessage) {
            $result = $this->chatMessage($vertexResult, $executionData);
            $vertexResult->setResult($result);
            $executionData->saveNodeContext($this->node->getNodeId(), $result);
            return;
        }

        // if是作为运行节点 仅记录，then结束when前execute
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

        // 暂时还是放到data库中，后续考虑放到 objectstorage 中
        $persistenceData = $executionData->getPersistenceData();
        $waitMessageEntity->setPersistentData($persistenceData);

        $waitMessageDomainService->save(
            dataIsolation: $executionData->getDataIsolation(),
            savingWaitMessageEntity: $waitMessageEntity
        );

        $vertexResult->setChildrenIds([]);
    }
}
