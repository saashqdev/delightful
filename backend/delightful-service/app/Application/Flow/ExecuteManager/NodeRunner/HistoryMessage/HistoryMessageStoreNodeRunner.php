<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\HistoryMessage;

use App\Application\Flow\ExecuteManager\Attachment\AbstractAttachment;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\Memory\LLMMemoryMessage;
use App\Application\Flow\ExecuteManager\Message\MessageUtil;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\HistoryMessage\HistoryMessageStoreNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\DelightfulFlowMessage;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use Hyperf\Odin\Message\Role;

#[FlowNodeDefine(
    type: NodeType::HistoryMessageStore->value,
    code: NodeType::HistoryMessageStore->name,
    name: '历史message / storage',
    paramsConfig: HistoryMessageStoreNodeParamsConfig::class,
    version: 'v0',
    singleDebug: false,
    needInput: false,
    needOutput: false,
)]
class HistoryMessageStoreNodeRunner extends NodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        /** @var HistoryMessageStoreNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();

        $delightfulFlowMessage = new DelightfulFlowMessage(
            $paramsConfig->getType(),
            $paramsConfig->getContent(),
            $paramsConfig->getLink(),
            $paramsConfig->getLinkDesc()
        );

        // if是资源类的data，那么need提前upload了
        $links = $delightfulFlowMessage->getLinks($executionData->getExpressionFieldData());
        $attachments = $this->recordFlowExecutionAttachments($executionData, $links);
        // 由于里面will进行重命名，所以这里直接get对应的名称传入进去
        $linkPaths = array_map(function (AbstractAttachment $attachment) {
            return $attachment->getPath();
        }, $attachments);

        $IMResponse = MessageUtil::getIMResponse($delightfulFlowMessage, $executionData, $linkPaths);
        if (empty($IMResponse)) {
            return;
        }
        $content = '';
        if ($IMResponse instanceof TextContentInterface) {
            $content = $IMResponse->getTextContent();
        }

        $LLMMemoryMessage = new LLMMemoryMessage(Role::User, $content, $executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId());
        $LLMMemoryMessage->setConversationId($executionData->getConversationId());
        $LLMMemoryMessage->setMessageId($executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId());
        $LLMMemoryMessage->setMountId($executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId());
        $LLMMemoryMessage->setAttachments($executionData->getTriggerData()->getAttachments());
        $LLMMemoryMessage->setOriginalContent(DelightfulFlowMessage::createContent($IMResponse));
        $LLMMemoryMessage->setTopicId($executionData->getTopicIdString());
        $LLMMemoryMessage->setRequestId($executionData->getId());
        $LLMMemoryMessage->setUid($executionData->getOperator()->getUid());
        $this->flowMemoryManager->receive(
            memoryType: $this->getMemoryType($executionData),
            LLMMemoryMessage: $LLMMemoryMessage,
            nodeDebug: $this->isNodeDebug($executionData),
        );
    }
}
